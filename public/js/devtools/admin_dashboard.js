/**
 * @file admin_dashboard.js
 * @description Gestisce la logica di visualizzazione della Dashboard Amministrativa.
 * Include la configurazione dei grafici Chart.js per le statistiche di sistema.
 * 
 * @author Soobadur Mohammad Ajmeer
 * @version 1.0.0
 */

document.addEventListener('DOMContentLoaded', function () {
    /**
     * Dati delle statistiche iniettati dalla vista.
     */
    const dataEl = document.getElementById('dashboard-data');
    const stats = dataEl ? JSON.parse(dataEl.textContent) : { attivi: 0, morosi: 0, trend_iscritti: [] };

    // Configurazione Comune Chart.js
    if (typeof Chart !== 'undefined') {
        Chart.defaults.color = '#94a3b8';
        Chart.defaults.scale.grid.color = 'rgba(255, 255, 255, 0.05)';
    }

    // 1. Status Chart (Doughnut) - Distribuzione soci attivi/morosi
    const canvasStatus = document.getElementById('chartStatus');
    if (canvasStatus) {
        const ctxStatus = canvasStatus.getContext('2d');
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: ['Attivi', 'Morosi'],
                datasets: [{
                    data: [stats.attivi, stats.morosi],
                    backgroundColor: ['#10b981', '#ef4444'], // Emerald-500, Red-500
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }
                }
            }
        });
    }

    // 2. Trend Chart (Line) - Iscrizioni mensili
    const canvasTrend = document.getElementById('chartTrend');
    if (canvasTrend) {
        const ctxTrend = canvasTrend.getContext('2d');
        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'],
                datasets: [{
                    label: 'Iscrizioni Validate',
                    data: stats.trend_iscritti,
                    borderColor: '#38bdf8', // Sky-400
                    backgroundColor: 'rgba(56, 189, 248, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#0f172a',
                    pointBorderColor: '#38bdf8',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }
});
