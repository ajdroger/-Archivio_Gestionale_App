/**
 * Statistics Page Logic
 * Location: public/js/pages/statistics.js
 * 
 * Handles Chart.js initialization, DataTables setup, and Real-time updates.
 */

document.addEventListener('DOMContentLoaded', function () {
    // --- 0. Data Extraction from DOM ---
    const statsContainer = document.getElementById('stats-data');
    if (!statsContainer) {
        console.error('Stats data container not found.');
        return;
    }

    const safeParseFloat = (val) => {
        const parsed = parseFloat(val);
        return isNaN(parsed) ? 0 : parsed;
    };

    const initialStats = {
        attivi: safeParseFloat(statsContainer.dataset.attivi),
        totale: safeParseFloat(statsContainer.dataset.totale),
        paganti: safeParseFloat(statsContainer.dataset.paganti),
        morosi: safeParseFloat(statsContainer.dataset.morosi)
    };

    // --- 1. init DataTables ---
    const table = $('.statistics-table').DataTable({
        "paging": true,
        "pageLength": 10,
        "lengthChange": false,
        "searching": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "language": {
            "search": "",
            "searchPlaceholder": "Cerca nella lista...",
            "paginate": { "previous": "<", "next": ">" },
            "info": "_START_-_END_ di _TOTAL_"
        },
        "dom": '<"d-flex justify-content-between mb-2"f>t<"d-flex justify-content-between mt-3"ip>',
        "initComplete": function () {
            $('.dataTables_filter input').addClass('form-control form-control-sm bg-dark text-white border-secondary');
        }
    });

    // --- 2. init Charts ---
    // Definisci i colori di default
    if (typeof Chart !== 'undefined') {
        Chart.defaults.color = '#94a3b8';
        Chart.defaults.borderColor = '#334155';

        const chartStatusCtx = document.getElementById('chartStatus');
        const chartFinancialCtx = document.getElementById('chartFinancial');

        let chartStatus = null;
        let chartFinancial = null;

        if (chartStatusCtx) {
            chartStatus = new Chart(chartStatusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Attivi', 'Altri'],
                    datasets: [{
                        data: [
                            initialStats.attivi,
                            initialStats.totale - initialStats.attivi
                        ],
                        backgroundColor: ['#0ea5e9', '#475569'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } } },
                    cutout: '70%'
                }
            });
        }

        if (chartFinancialCtx) {
            chartFinancial = new Chart(chartFinancialCtx, {
                type: 'doughnut',
                data: {
                    labels: ['In Regola', 'Morosi'],
                    datasets: [{
                        data: [
                            initialStats.paganti,
                            initialStats.morosi
                        ],
                        backgroundColor: ['#22c55e', '#ef4444'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } } },
                    cutout: '70%'
                }
            });
        }

        // Make charts available for updateUI
        window.statsCharts = {
            status: chartStatus,
            financial: chartFinancial
        };
    }

    // --- 3. Real-time Engine ---
    function updateUI(data) {
        // Update Headers
        const dataRef = document.getElementById('data-ref');
        if (dataRef) dataRef.textContent = data.stats.data_riferimento;

        // Update KPI Cards
        // Note: Selectors are specific to the template structure. 
        // Using more robust ID-based selectors would be better in a future refactor.
        const totalEl = document.querySelector('.col-12 h3.text-white');
        if (totalEl) totalEl.textContent = data.stats.totale;

        // Note: Using nth-child is fragile, but maintained for compatibility with current markup
        const percentPagantiEl = document.querySelector('.col-6:nth-child(2) h4');
        if (percentPagantiEl) percentPagantiEl.textContent = data.stats.perc_paganti + '%';

        const percentMorosiEl = document.querySelector('.col-6:nth-child(3) h4');
        if (percentMorosiEl) percentMorosiEl.textContent = data.stats.perc_morosi + '%';

        // Update Badges in Chart sections
        const badgeInfo = document.querySelector('.badge.bg-info');
        if (badgeInfo) badgeInfo.textContent = 'ATTIVI: ' + data.stats.attivi;

        const badgeSecondary = document.querySelector('.badge.bg-secondary');
        if (badgeSecondary) badgeSecondary.textContent = 'TOTALE: ' + data.stats.totale;

        const badgeSuccess = document.querySelector('.badge.bg-success');
        if (badgeSuccess) badgeSuccess.textContent = 'IN REGOLA: ' + data.stats.paganti;

        const badgeDanger = document.querySelector('.badge.bg-danger');
        if (badgeDanger) badgeDanger.textContent = 'MOROSI: ' + data.stats.morosi;

        // Update Charts if they exist
        if (window.statsCharts) {
            if (window.statsCharts.status) {
                window.statsCharts.status.data.datasets[0].data = [data.stats.attivi, data.stats.totale - data.stats.attivi];
                window.statsCharts.status.update();
            }
            if (window.statsCharts.financial) {
                window.statsCharts.financial.data.datasets[0].data = [data.stats.paganti, data.stats.morosi];
                window.statsCharts.financial.update();
            }
        }

        // Update Monitoring
        const dbEl = document.getElementById('mon-db');
        if (dbEl) {
            dbEl.innerHTML = data.monitoring.database.status ?
                '<span class="badge bg-success-subtle text-success border border-success border-opacity-25">OPERATIVO</span>' :
                '<span class="badge bg-danger-subtle text-danger border border-danger border-opacity-25">ERRORE</span>';
        }

        const storageEl = document.getElementById('mon-storage');
        if (storageEl) {
            storageEl.innerHTML = '<span class="badge bg-success-subtle text-success border border-success border-opacity-25">' + data.health.checks.storage.message + '</span>';
            const storageSmall = document.querySelector('#mon-storage + small');
            if (storageSmall) storageSmall.textContent = data.health.checks.storage.free_space + ' LIBERI';
        }

        const backupsEl = document.getElementById('mon-backups');
        if (backupsEl) {
            backupsEl.innerHTML = data.monitoring.backups.status ?
                '<span class="badge bg-success-subtle text-success border border-success border-opacity-25">' + data.monitoring.backups.count + ' BACKUPS</span>' :
                '<span class="badge bg-warning-subtle text-warning border border-warning border-opacity-25">ALERT</span>';
        }

        const uptimeEl = document.getElementById('mon-uptime');
        if (uptimeEl) {
            uptimeEl.innerHTML = '<span class="badge bg-dark text-white border border-secondary">' + data.health.uptime + '</span>';
        }
    }

    async function fetchRealTime() {
        try {
            const indicator = document.getElementById('live-indicator');
            if (indicator) indicator.classList.replace('text-success', 'text-warning');

            const response = await fetch('?action=api');
            const data = await response.json();

            updateUI(data);

            if (indicator) indicator.classList.replace('text-warning', 'text-success');
        } catch (error) {
            console.error('Real-time update failed:', error);
            const indicator = document.getElementById('live-indicator');
            if (indicator) indicator.classList.replace('text-success', 'text-danger');
        }
    }

    // Poll every 10 seconds
    setInterval(fetchRealTime, 10000);
});
