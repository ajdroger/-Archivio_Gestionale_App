/**
 * statistics.js
 * Advanced Charting for MCAG Financial Intelligence
 */

document.addEventListener('DOMContentLoaded', function () {

    const trendCtx = document.getElementById('adminTrendChart');
    const demoCtx = document.getElementById('adminDemoChart');

    if (trendCtx) {
        // Gradient for Trend
        const gradient = trendCtx.getContext('2d').createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.5)'); // Blue
        gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'],
                datasets: [
                    {
                        label: 'Incassi Reali 2025',
                        data: [12, 19, 15, 25, 32, 35, 45, 48, 52, 60, 65, 75], // Mock Data
                        borderColor: '#3b82f6', // Blue
                        backgroundColor: gradient,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Proiezione IA 2026',
                        data: [75, 80, 85, 92, 98, 105, 115, 120, 128, 135, 142, 150], // Mock Data
                        borderColor: '#10b981', // Emerald
                        borderDash: [5, 5],
                        borderWidth: 2,
                        tension: 0.4,
                        pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { color: '#94a3b8' } },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8' } },
                    x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
                }
            }
        });
    }

    if (demoCtx) {
        new Chart(demoCtx, {
            type: 'doughnut',
            data: {
                labels: ['Civili', 'Militari', 'Ex-Militari'],
                datasets: [{
                    data: [45, 35, 20],
                    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#94a3b8', padding: 20 } }
                },
                cutout: '70%'
            }
        });
    }
});
