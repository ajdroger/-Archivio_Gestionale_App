/**
 * Expensebar Dashboard Logic
 * Premium Financial Management for MCAG
 */

const Expensebar = {
    state: {
        currentMonth: new Date().getMonth() + 1,
        currentYear: new Date().getFullYear(),
        expenses: [],
        chartInstance: null,
        pieInstance: null,
        categories: {
            'Food & Dining': '#10b981', // Emerald
            'Transportation': '#3b82f6', // Blue
            'Shopping': '#f59e0b', // Amber
            'Entertainment': '#8b5cf6', // Violet
            'Bills & Utilities': '#ef4444', // Red
            'Healthcare': '#ec4899', // Pink
            'Travel': '#06b6d4', // Cyan
            'Education': '#6366f1', // Indigo
            'Other': '#64748b' // Slate
        }
    },

    init() {
        this.bindEvents();
        this.fetchData();
        this.updateDateDisplay();
    },

    bindEvents() {
        // Navigation
        document.getElementById('prevMonth').addEventListener('click', () => this.changeMonth(-1));
        document.getElementById('nextMonth').addEventListener('click', () => this.changeMonth(1));

        // Forms
        document.getElementById('quickExpenseForm').addEventListener('submit', (e) => this.handleAddExpense(e));

        // Mock Year Filter
        document.getElementById('trendYearMock').addEventListener('change', (e) => {
            this.state.currentYear = parseInt(e.target.value);
            this.fetchData();
        });
    },

    changeMonth(delta) {
        let newMonth = this.state.currentMonth + delta;
        if (newMonth > 12) {
            newMonth = 1;
            this.state.currentYear++;
        } else if (newMonth < 1) {
            newMonth = 12;
            this.state.currentYear--;
        }
        this.state.currentMonth = newMonth;
        this.updateDateDisplay();
        this.fetchData();
    },

    updateDateDisplay() {
        const monthNames = ["Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno",
            "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre"];
        document.getElementById('currentMonthDisplay').innerText =
            `${monthNames[this.state.currentMonth - 1]} ${this.state.currentYear}`;
    },

    async fetchData() {
        try {
            const response = await fetch(`${window.MCAG_API_URL}/expenses?month=${this.state.currentMonth}&year=${this.state.currentYear}`);
            if (!response.ok) throw new Error("Errore network");

            const data = await response.json();
            this.state.expenses = data;

            this.renderKPI();
            this.renderTable();
            this.renderCharts();
        } catch (error) {
            console.error(error);
            Swal.fire('Errore', 'Impossibile caricare i dati finanziari.', 'error');
        }
    },

    renderKPI() {
        const total = this.state.expenses.reduce((acc, curr) => acc + parseFloat(curr.amount), 0);
        const categories = new Set(this.state.expenses.map(e => e.category)).size;

        // Count days passed in month (or total days if past month)
        const now = new Date();
        const daysInMonth = (this.state.currentMonth === now.getMonth() + 1 && this.state.currentYear === now.getFullYear())
            ? now.getDate()
            : new Date(this.state.currentYear, this.state.currentMonth, 0).getDate();

        const dailyAvg = daysInMonth > 0 ? (total / daysInMonth) : 0;

        // Animate Numbers
        this.animateValue("totalAmount", total, "currency");
        this.animateValue("dailyAverage", dailyAvg, "currency");
        this.animateValue("activeCategories", categories, "int");
    },

    animateValue(id, value, type) {
        const el = document.getElementById(id);
        const current = el.innerText.replace('€', '').replace(',', ''); // Simplified
        const start = 0; // Ideally animate from current

        // Simple Set for now (Animation logic can be complex)
        el.innerText = type === 'currency'
            ? new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(value)
            : value;
    },

    renderTable() {
        const tbody = document.getElementById('transactionTableBody');
        tbody.innerHTML = '';

        if (this.state.expenses.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-8 text-center text-gray-500 text-xs">Nessuna transazione in questo periodo.</td></tr>`;
            return;
        }

        this.state.expenses.forEach(expense => {
            const dateObj = new Date(expense.date);
            const dateStr = dateObj.toLocaleDateString('it-IT', { day: '2-digit', month: 'short' });
            const timeStr = dateObj.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
            const amount = parseFloat(expense.amount).toLocaleString('it-IT', { style: 'currency', currency: 'EUR' });
            const catColor = this.state.categories[expense.category] || '#64748b';

            const tr = document.createElement('tr');
            tr.className = "hover:bg-white/5 transition-colors group border-b border-gray-800 last:border-0";
            tr.innerHTML = `
                <td class="px-4 py-3 font-mono text-gray-400 text-xs">
                    <div>${dateStr}</div>
                    <div class="text-[10px] opacity-60">${timeStr}</div>
                </td>
                <td class="px-4 py-3 font-medium text-white">${expense.description}</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider text-white" style="background-color: ${catColor}20; color: ${catColor}; border: 1px solid ${catColor}40">
                        ${expense.category}
                    </span>
                </td>
                <td class="px-4 py-3 text-right font-mono text-emerald-400 font-bold">${amount}</td>
                <td class="px-4 py-3 text-center">
                    <button onclick="Expensebar.deleteExpense(${expense.id})" class="p-1.5 text-gray-500 hover:text-red-400 transition-colors opacity-0 group-hover:opacity-100">
                        <i data-feather="trash-2" class="w-4 h-4"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
        feather.replace();
    },

    async handleAddExpense(e) {
        e.preventDefault();

        const desc = document.getElementById('descInput').value;
        const amount = document.getElementById('amountInput').value;
        const cat = document.getElementById('categoryInput').value;
        const date = document.getElementById('dateInput').value;

        if (!desc || !amount || !date) {
            Swal.fire('Attenzione', 'Compila tutti i campi.', 'warning');
            return;
        }

        try {
            const response = await fetch(`${window.MCAG_API_URL}/expenses/add`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ description: desc, amount: amount, category: cat, date: date })
            });

            if (response.ok) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Spesa aggiunta',
                    showConfirmButton: false,
                    timer: 3000
                });
                document.getElementById('quickExpenseForm').reset();

                // Reset to current time
                const now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                document.getElementById('dateInput').value = now.toISOString().slice(0, 16);

                this.fetchData(); // Refresh
            } else {
                throw new Error();
            }
        } catch (err) {
            Swal.fire('Errore', 'Salvataggio fallito.', 'error');
        }
    },

    async deleteExpense(id) {
        const result = await Swal.fire({
            title: 'Eliminare?',
            text: "Questa azione è irreversibile.",
            icon: 'warning',
            background: '#0f172a',
            color: '#fff',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Sì, elimina',
            cancelButtonText: 'Annulla'
        });

        if (result.isConfirmed) {
            try {
                await fetch(`${window.MCAG_API_URL}/expenses/${id}/delete`, { method: 'POST' });
                this.fetchData();
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Eliminata',
                    showConfirmButton: false,
                    timer: 2000,
                    background: '#0f172a',
                    color: '#fff'
                });
            } catch (err) {
                Swal.fire('Errore', 'Impossibile eliminare.', 'error');
            }
        }
    },

    renderCharts() {
        // Prepare Data
        const catTotals = {};
        this.state.expenses.forEach(e => {
            catTotals[e.category] = (catTotals[e.category] || 0) + parseFloat(e.amount);
        });

        const labels = Object.keys(catTotals);
        const data = Object.values(catTotals);
        const colors = labels.map(l => this.state.categories[l] || '#64748b');

        // Pie Chart
        const ctxPie = document.getElementById('categoryChart').getContext('2d');
        if (this.state.pieInstance) this.state.pieInstance.destroy();

        this.state.pieInstance = new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 0,
                    hoverOffset: 10
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

        this.renderLegend(labels, colors, data);

        // Trend Chart (Mock Monthly) - In real app, fetch from /api/stats/trend
        // For now, let's visualize daily spend in current month
        const daily = {};
        this.state.expenses.forEach(e => {
            const d = new Date(e.date).getDate();
            daily[d] = (daily[d] || 0) + parseFloat(e.amount);
        });

        const days = Array.from({ length: 31 }, (_, i) => i + 1);
        const trendData = days.map(d => daily[d] || 0);

        const ctxTrend = document.getElementById('trendChart').getContext('2d');
        if (this.state.chartInstance) this.state.chartInstance.destroy();

        // Create Gradient
        const gradient = ctxTrend.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(16, 185, 129, 0.5)'); // Emerald 500
        gradient.addColorStop(1, 'rgba(16, 185, 129, 0)');

        this.state.chartInstance = new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: days,
                datasets: [{
                    label: 'Spesa Giornaliera',
                    data: trendData,
                    borderColor: '#10b981',
                    backgroundColor: gradient,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 6
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
                        beginAtZero: true,
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { color: '#94a3b8' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', maxTicksLimit: 10 }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
            }
        });
    },

    renderLegend(labels, colors, data) {
        const container = document.getElementById('categoryLegend');
        container.innerHTML = labels.map((l, i) => `
            <div class="flex items-center justify-between text-xs">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full" style="background-color: ${colors[i]}"></span>
                    <span class="text-gray-300">${l}</span>
                </div>
                <span class="text-white font-mono font-bold">€${data[i].toFixed(2)}</span>
            </div>
         `).join('');
    }
};

// Start
document.addEventListener('DOMContentLoaded', () => {
    Expensebar.init();
});
