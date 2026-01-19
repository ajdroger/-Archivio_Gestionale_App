// Expensebar Genius Logic (v2.0)

const state = {
    currentDate: new Date(),
    expenses: [],
    trendData: [],
    categoryData: [],
    charts: {
        trend: null,
        category: null
    }
};

const getApiUrl = (endpoint) => (window.MCAG_BASE_URL || '') + '/expensebar/api' + endpoint;

// Init
const initApp = async () => {
    updateDateDisplay();
    await loadDashboardData();
    setupEventListeners();
    initCharts();
};

const setupEventListeners = () => {
    const prevBtn = document.getElementById('prevMonth');
    if (prevBtn) prevBtn.addEventListener('click', () => changeMonth(-1));

    const nextBtn = document.getElementById('nextMonth');
    if (nextBtn) nextBtn.addEventListener('click', () => changeMonth(1));

    const quickForm = document.getElementById('quickExpenseForm');
    if (quickForm) quickForm.addEventListener('submit', handleAddExpense);

    const trendMock = document.getElementById('trendYearMock');
    if (trendMock) {
        trendMock.addEventListener('change', (e) => {
            state.currentDate.setFullYear(parseInt(e.target.value));
            loadDashboardData();
        });
    }
};

const changeMonth = (delta) => {
    state.currentDate.setMonth(state.currentDate.getMonth() + delta);
    updateDateDisplay();
    loadDashboardData();
};

const updateDateDisplay = () => {
    const options = { month: 'long', year: 'numeric' };
    const dateStr = state.currentDate.toLocaleDateString('it-IT', options);
    // Capitalize first letter
    const formatted = dateStr.charAt(0).toUpperCase() + dateStr.slice(1);
    const display = document.getElementById('currentMonthDisplay');
    if (display) display.textContent = formatted;
};

// Data Loading
const loadDashboardData = async () => {
    const month = state.currentDate.getMonth() + 1;
    const year = state.currentDate.getFullYear();

    try {
        // Parallel Fetch
        const [expensesRes, catsRes, trendRes] = await Promise.all([
            fetch(`${getApiUrl('/expenses')}?month=${month}&year=${year}`),
            fetch(`${getApiUrl('/stats/categories')}?month=${month}&year=${year}`),
            fetch(`${getApiUrl('/stats/trend')}?year=${year}`)
        ]);

        state.expenses = await expensesRes.json();
        state.categoryData = await catsRes.json();
        const trendRaw = await trendRes.json();
        state.trendData = trendRaw.data;

        updateKPIs();
        renderTransactionTable();
        updateCharts();

    } catch (e) {
        console.error("Dashboard Sync Failed", e);
    }
};

const updateKPIs = () => {
    // Total
    const total = state.expenses.reduce((sum, exp) => sum + parseFloat(exp.amount), 0);
    animateValue('totalAmount', total, '€');

    // Categories Count
    const uniqueCats = new Set(state.expenses.map(e => e.category)).size;
    const activeCats = document.getElementById('activeCategories');
    if (activeCats) activeCats.textContent = uniqueCats;

    // Daily Avg (simple)
    const daysInMonth = new Date(state.currentDate.getFullYear(), state.currentDate.getMonth() + 1, 0).getDate();
    const now = new Date();
    let divisor = daysInMonth;
    if (state.currentDate.getMonth() === now.getMonth() && state.currentDate.getFullYear() === now.getFullYear()) {
        divisor = now.getDate();
    }
    const avg = total / (divisor || 1);
    animateValue('dailyAverage', avg, '€');
};

const animateValue = (id, end, prefix = '') => {
    const obj = document.getElementById(id);
    if (!obj) return;
    obj.textContent = prefix + end.toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

// Charts
const initCharts = () => {
    if (typeof Chart === 'undefined') return;

    Chart.defaults.font.family = 'Inter, sans-serif';
    Chart.defaults.color = '#9ca3af';

    // Trend Chart
    const trendCanvas = document.getElementById('trendChart');
    if (trendCanvas) {
        const ctxTrend = trendCanvas.getContext('2d');
        const gradient = ctxTrend.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(16, 185, 129, 0.5)'); // Emerald 500
        gradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

        state.charts.trend = new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'],
                datasets: [{
                    label: 'Spesa',
                    data: [],
                    borderColor: '#10b981',
                    backgroundColor: gradient,
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#064e3b',
                    pointBorderColor: '#10b981'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { grid: { color: 'rgba(255,255,255,0.05)' }, beginAtZero: true },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Category Chart
    const catCanvas = document.getElementById('categoryChart');
    if (catCanvas) {
        const ctxCat = catCanvas.getContext('2d');
        state.charts.category = new Chart(ctxCat, {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: [
                        '#10b981', '#8b5cf6', '#3b82f6', '#f59e0b', '#ef4444', '#ec4899', '#6366f1'
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
};

const updateCharts = () => {
    // Trend
    if (state.charts.trend) {
        state.charts.trend.data.datasets[0].data = state.trendData;
        state.charts.trend.update();
    }

    // Categories
    if (state.charts.category) {
        const labels = state.categoryData.map(c => c.category);
        const data = state.categoryData.map(c => c.total);
        state.charts.category.data.labels = labels;
        state.charts.category.data.datasets[0].data = data;
        state.charts.category.update();
        renderLegend(labels, data, state.charts.category.data.datasets[0].backgroundColor);
    }
};

const renderLegend = (labels, data, colors) => {
    const container = document.getElementById('categoryLegend');
    if (!container) return;

    container.innerHTML = labels.map((label, i) => `
        <div class="flex items-center justify-between text-xs">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full" style="background-color: ${colors[i % colors.length]}"></span>
                <span class="text-gray-300">${label}</span>
            </div>
            <span class="font-medium text-white">€${parseFloat(data[i]).toFixed(2)}</span>
        </div>
    `).join('');
};

const renderTransactionTable = () => {
    const tbody = document.getElementById('transactionTableBody');
    if (!tbody) return;

    tbody.innerHTML = state.expenses.map(exp => `
        <tr class="hover:bg-white/5 transition-colors group">
            <td class="px-4 py-3 text-gray-400 font-mono text-xs">${exp.date}</td>
            <td class="px-4 py-3 font-medium text-white">${exp.description}</td>
            <td class="px-4 py-3">
                <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-gray-800 text-gray-300 border border-gray-700">
                    ${exp.category}
                </span>
            </td>
            <td class="px-4 py-3 text-right font-mono text-emerald-400">€${parseFloat(exp.amount).toFixed(2)}</td>
            <td class="px-4 py-3 text-center">
                <button onclick="handleDelete(${exp.id})" class="text-gray-600 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100">
                    <i data-feather="trash-2" class="w-3.5 h-3.5"></i>
                </button>
            </td>
        </tr>
    `).join('');
    if (typeof feather !== 'undefined') feather.replace();
};

const handleAddExpense = async (e) => {
    e.preventDefault();
    const desc = document.getElementById('descInput').value;
    const amount = document.getElementById('amountInput').value;
    const cat = document.getElementById('categoryInput').value;
    const date = document.getElementById('dateInput').value;

    const data = { description: desc, amount: amount, category: cat, date: date };

    try {
        await fetch(getApiUrl('/expenses'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        // Refresh
        document.getElementById('quickExpenseForm').reset();
        document.getElementById('dateInput').valueAsDate = new Date(); // Reset date to today
        loadDashboardData(); // Reloads all kpis and charts

    } catch (err) {
        alert("Errore salvataggio");
    }
};

const handleDelete = async (id) => {
    if (!confirm("Eliminare questa spesa?")) return;
    try {
        await fetch(getApiUrl(`/expenses/${id}`), { method: 'DELETE' });
        loadDashboardData();
    } catch (e) { console.error(e); }
};

window.initApp = initApp;
window.handleDelete = handleDelete; // Expose for inline onclick