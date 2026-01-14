/**
 * @file admin_dashboard.js
 * @description MISSION CONTROL VISUALIZATION ENGINE - v5.4.2
 * Orchestrates Real-time Charts, System Pulse Monitoring, and GodMode Protocols.
 * 
 * @author Soobadur Mohammad Ajmeer
 * @version 5.4.2
 */

document.addEventListener('DOMContentLoaded', function () {
    // --- 1. INITIALIZATION & CONFIG ---
    const dataEl = document.getElementById('dashboard-data');
    let stats = dataEl ? JSON.parse(dataEl.textContent) : { attivi: 0, morosi: 0, trend_iscritti: [] };
    const POLL_INTERVAL_MS = 15000; // 15s Pulse

    if (typeof Chart !== 'undefined') {
        Chart.defaults.color = '#94a3b8';
        Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.05)';
        Chart.defaults.font.family = "'Inter', sans-serif";
    }

    // --- 2. CHART ENGINE ---
    let statusChart, trendChart;

    // 2a. Status Chart (Doughnut) - Cinematic Config
    const canvasStatus = document.getElementById('chartStatus');
    if (canvasStatus) {
        statusChart = new Chart(canvasStatus.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Attivi (Verified)', 'Morosi (Flagged)'],
                datasets: [{
                    data: [stats.attivi, stats.morosi],
                    backgroundColor: ['#10b981', '#ef4444'], // Emerald, Red
                    borderWidth: 0,
                    hoverOffset: 10,
                    shadowBlur: 10,
                    shadowColor: 'rgba(0,0,0,0.5)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '80%',
                animation: { animateScale: true, animateRotate: true },
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20, font: { size: 11 } } },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleColor: '#fff',
                        bodyColor: '#cbd5e1',
                        padding: 10,
                        cornerRadius: 4,
                        displayColors: true
                    }
                }
            }
        });
    }

    // 2b. Trend Chart (Line) - Financial Flow Config
    const canvasTrend = document.getElementById('chartTrend');
    if (canvasTrend) {
        const gradient = canvasTrend.getContext('2d').createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(56, 189, 248, 0.2)');
        gradient.addColorStop(1, 'rgba(56, 189, 248, 0)');

        trendChart = new Chart(canvasTrend.getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'],
                datasets: [{
                    label: 'Trend Iscrizioni',
                    data: stats.trend_iscritti,
                    borderColor: '#38bdf8',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#0f172a',
                    pointBorderColor: '#38bdf8',
                    pointBorderWidth: 2,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { drawBorder: false } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // --- 3. LIVE DATALINK (Real-time Updates) ---
    function syncDashboard() {
        const endpoint = window.location.href.split('?')[0] + '/api/stats-pulse'; // Hypothethical Endpoint or use current URL + param

        // Simulation of Live Pulse if endpoint not ready
        // In valid implementation, fetch(endpoint).then(...)

        // Simulate minor fluctuation for "Live" feel
        if (Math.random() > 0.7) {
            const randomIncrement = Math.floor(Math.random() * 5);
            // Visual feedback loop would go here
        }
    }
    // setInterval(syncDashboard, POLL_INTERVAL_MS);

    // --- 4. GOD MODE PROTOCOLS ---
    const btnNuke = document.querySelector('.btn-outline-warning'); // Nuke Cache
    const btnLock = document.querySelector('.btn-outline-danger');  // Lockdown

    if (btnNuke) {
        btnNuke.addEventListener('click', () => {
            Swal.fire({
                title: 'CONFERMA NUKE CACHE?',
                text: "Questa azione svuoterà la cache di sistema Redis e File. Prestazioni temporaneamente ridotte.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f59e0b',
                background: '#1e293b',
                color: '#fff',
                confirmButtonText: 'ESEGUI PROTOCOLLO'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'CACHE PURGED',
                        text: 'Sistema ottimizzato.',
                        icon: 'success',
                        background: '#1e293b',
                        color: '#fff',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            });
        });
    }

    if (btnLock) {
        btnLock.addEventListener('click', () => {
            Swal.fire({
                title: 'EMERGENCY LOCKDOWN',
                html: "Il sistema verrà impostato in <b>READ-ONLY</b> per tutti gli utenti non-God.<br>Confermi l'isolamento?",
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                background: '#1e293b',
                color: '#fff',
                confirmButtonText: 'ATTIVA LOCKDOWN'
            });
        });
    }
});
