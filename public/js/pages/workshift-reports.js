
document.addEventListener('DOMContentLoaded', () => {
    console.log('[Reports] Initializing...');

    if (!window.REPORT_DATA || !window.Chart) {
        console.warn('[Reports] Missing data or Chart.js library');
        return;
    }

    const data = window.REPORT_DATA;

    // --- Chart 1: Daily Hours Trend ---
    const ctxTrend = document.getElementById('overtimeChart');
    if (ctxTrend) {
        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: data.trend.labels,
                datasets: [{
                    label: 'Ore Lavorate',
                    data: data.trend.data,
                    borderColor: '#22d3ee', // Cyan-400
                    backgroundColor: 'rgba(34, 211, 238, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#0f172a',
                    pointBorderColor: '#22d3ee',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleColor: '#fff',
                        bodyColor: '#cbd5e1',
                        borderColor: 'rgba(255,255,255,0.1)',
                        borderWidth: 1
                    }
                },
                scales: {
                    y: {
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { color: '#94a3b8' },
                        beginAtZero: true
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8' }
                    }
                }
            }
        });
    }

    // --- Chart 2: Role Distribution (Cost Proxy) ---
    const ctxPie = document.getElementById('laborCostChart');
    if (ctxPie) {
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: data.distribution.labels,
                datasets: [{
                    data: data.distribution.data,
                    backgroundColor: [
                        '#6366f1', // Indigo
                        '#22d3ee', // Cyan
                        '#a855f7', // Purple
                        '#ec4899', // Pink
                        '#10b981', // Emerald
                        '#f59e0b'  // Amber
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
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#cbd5e1',
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 20,
                            font: { size: 10 }
                        }
                    }
                }
            }
        });
    }
});
