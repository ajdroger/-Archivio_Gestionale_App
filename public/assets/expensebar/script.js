
// Expensebar Singular Logic
const initApp = () => {
    loadExpenses();
    setupEventListeners();
    initChart();
};

const getApiUrl = () => (window.MCAG_BASE_URL || '') + '/expensebar/api/expenses';

// State
let expenses = [];
let expenseChart = null;

const setupEventListeners = () => {
    const form = document.getElementById('expenseForm');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const desc = document.getElementById('description').value;
            const amount = document.getElementById('amount').value;
            const category = document.getElementById('category').value;
            const date = document.getElementById('year').value + '-' +
                String(document.getElementById('date_month') ? document.getElementById('date_month').value : '01').padStart(2, '0') + '-' +
                String(document.getElementById('day').value).padStart(2, '0');

            await addExpense({ description: desc, amount: amount, category: category, date: date });
            form.reset();
        });
    }

    const refreshBtn = document.getElementById('refreshChart');
    if (refreshBtn) refreshBtn.addEventListener('click', loadExpenses);

    // AI Forecast Button (Inject if not present, or assume exists)
    // We'll add a check for a new ID 'forecastBtn'
    const aiBtn = document.getElementById('aiForecastBtn');
    if (aiBtn) aiBtn.addEventListener('click', showForecast);
};

const showForecast = async () => {
    showToast('Consulting AI Model...', 'info');
    try {
        const res = await fetch((window.MCAG_BASE_URL || '') + '/expensebar/api/forecast');
        const data = await res.json();

        if (data.error) throw new Error(data.error);

        // Show result
        alert(`🔮 AI Forecast for Next Month:\nEstimated Expense: €${data.forecast}\nTrend: ${data.trend}\nConfidence: ${data.confidence * 100}%`);

    } catch (e) {
        console.error(e);
        showToast('Forecast failed', 'error');
    }
};

const loadExpenses = async () => {
    try {
        const res = await fetch(getApiUrl());
        expenses = await res.json();
        renderTable();
        updateChart();
    } catch (e) {
        console.error("Failed to load expenses", e);
    }
};

const addExpense = async (data) => {
    try {
        const res = await fetch(getApiUrl(), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (result.status === 'success') {
            showToast('Spesa salvata!', 'success');
            loadExpenses();
        }
    } catch (e) {
        showToast('Errore nel salvataggio', 'error');
    }
};

const renderTable = () => {
    const tbody = document.getElementById('expensesTable');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (expenses.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center p-4 text-mcag-muted">Nessuna spesa registrata.</td></tr>`;
        return;
    }

    expenses.slice(0, 5).forEach(exp => {
        const tr = document.createElement('tr');
        tr.className = 'border-b border-white/5 hover:bg-white/5 transition-colors';
        tr.innerHTML = `
            <td class="px-4 py-3 text-mcag-text">${exp.date}</td>
            <td class="px-4 py-3 text-mcag-text">
                <span class="px-2 py-1 bg-mcag-accent/10 text-mcag-accent text-xs rounded-full">${exp.category}</span>
            </td>
            <td class="px-4 py-3 text-mcag-text font-bold">€ ${parseFloat(exp.amount).toFixed(2)}</td>
            <td class="px-4 py-3 text-mcag-text">
                <button class="text-red-400 hover:text-red-300"><i data-feather="trash-2" class="w-4 h-4"></i></button>
            </td>
        `;
        tbody.appendChild(tr);
    });
    if (typeof feather !== 'undefined') feather.replace();
};

const initChart = () => {
    const ctx = document.getElementById('expenseChart');
    if (!ctx) return;

    // MCAG Colors
    Chart.defaults.color = '#94a3b8';
    Chart.defaults.font.family = 'Inter';

    expenseChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Spese (€)',
                data: [0, 0, 0, 0, 0, 0],
                borderColor: '#38bdf8', // mcag-accent
                backgroundColor: 'rgba(56, 189, 248, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    beginAtZero: true
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
};

const updateChart = () => {
    if (!expenseChart) return;
    // Simple aggregation logic for chart
    // Group by month (simplified)
    const monthly = {};
    const months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
    months.forEach(m => monthly[m] = 0);

    expenses.forEach(e => {
        const m = e.date.split('-')[1];
        if (monthly[m] !== undefined) monthly[m] += parseFloat(e.amount);
    });

    // Update chart data
    // Assuming current year view generally
    const labels = months.map(m => new Date(2024, parseInt(m) - 1, 1).toLocaleString('default', { month: 'short' }));
    const data = months.map(m => monthly[m]);

    expenseChart.data.labels = labels;
    expenseChart.data.datasets[0].data = data;
    expenseChart.update();
};

const showToast = (msg, type) => {
    // Simple toast fallback
    alert(msg);
};

window.addExpense = addExpense; // Expose for debugging