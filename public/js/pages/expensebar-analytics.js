/**
 * Expensebar Analytics Logic
 * Advanced visualization for MCAG
 */

const Analytics = {
    state: {
        year: new Date().getFullYear(),
        colors: {
            'Food & Dining': '#8b5cf6', // Violet
            'Transportation': '#10b981', // Emerald
            'Shopping': '#f59e0b', // Amber
            'Entertainment': '#ec4899', // Pink
            'Bills & Utilities': '#ef4444', // Red
            'Healthcare': '#06b6d4', // Cyan
            'Travel': '#3b82f6', // Blue
            'Education': '#6366f1', // Indigo
            'Other': '#64748b' // Slate
        }
    },

    init() {
        this.bindEvents();
        this.fetchData();
    },

    bindEvents() {
        document.getElementById('analyticsYear').addEventListener('change', (e) => {
            this.state.year = parseInt(e.target.value);
            this.fetchData();
        });
    },

    async fetchData() {
        try {
            // Parallel Fetch
            const [catRes, trendRes] = await Promise.all([
                fetch(`${window.MCAG_API_URL}/stats/category?year=${this.state.year}`),
                fetch(`${window.MCAG_API_URL}/stats/trend?year=${this.state.year}`)
            ]);

            if (!catRes.ok || !trendRes.ok) throw new Error('Network error');

            const catData = await catRes.json();
            const trendData = await trendRes.json();

            this.renderCategoryChart(catData);
            this.renderTrendChart(trendData);
            this.renderStatsTable(catData);

        } catch (error) {
            console.error(error);
            Swal.fire('Errore', 'Impossibile caricare le statistiche.', 'error');
        }
    },

    renderCategoryChart(data) {
        const ctx = document.getElementById('categoryChart').getContext('2d');
        if (this.catChart) this.catChart.destroy();

        const labels = data.map(d => d.category);
        const values = data.map(d => parseFloat(d.total));
        const colors = labels.map(l => this.state.colors[l] || '#94a3b8');

        this.catChart = new Chart(ctx, {
            type: 'polarArea',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors.map(c => c + '80'), // 50% opacity
                    borderColor: colors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { display: false, backdropColor: 'transparent' }
                    }
                },
                plugins: {
                    legend: { position: 'right', labels: { color: '#cbd5e1' } }
                }
            }
        });
    },

    renderTrendChart(payload) {
        const ctx = document.getElementById('yearlyChart').getContext('2d');
        if (this.trendChart) this.trendChart.destroy();

        // Payload structure check: { year: 2026, data: [0, 100, ...] }
        const values = payload.data;
        const months = ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'];

        // Gradient
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(16, 185, 129, 0.5)');
        gradient.addColorStop(1, 'rgba(16, 185, 129, 0)');

        this.trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: `Spesa ${this.state.year}`,
                    data: values,
                    borderColor: '#10b981',
                    backgroundColor: gradient,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
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
                        ticks: { color: '#94a3b8' }
                    }
                }
            }
        });
    },

    renderStatsTable(data) {
        const tbody = document.getElementById('categoryStatsTable');
        const totalOverall = data.reduce((acc, curr) => acc + parseFloat(curr.total), 0);

        if (data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">Nessun dato disponibile.</td></tr>`;
            return;
        }

        tbody.innerHTML = data.map(d => {
            const amount = parseFloat(d.total);
            const percentage = totalOverall > 0 ? ((amount / totalOverall) * 100).toFixed(1) : 0;
            const avg = (amount / 12).toFixed(2); // Mock average per month

            return `
                <tr class="hover:bg-white/5 transition-colors border-b border-gray-800 last:border-0">
                    <td class="px-4 py-3 font-medium text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full" style="background-color: ${this.state.colors[d.category] || '#94a3b8'}"></span>
                        ${d.category}
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-emerald-400 font-bold">€${amount.toFixed(2)}</td>
                    <td class="px-4 py-3 text-right font-mono text-gray-400">€${avg}</td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <span class="text-xs text-gray-400 w-8">${percentage}%</span>
                            <div class="w-16 h-1.5 bg-gray-800 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full" style="width: ${percentage}%"></div>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }
};

document.addEventListener('DOMContentLoaded', () => {
    Analytics.init();
});
