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
                labels: ['2021', '2022', '2023', '2024', '2025', '2026 (AI)', '2027 (AI)', '2028 (AI)', '2029 (AI)', '2030 (AI)'],
                datasets: [
                    {
                        label: 'Incassi Storici',
                        data: [18000, 19500, 22000, 23500, 24500, null, null, null, null, null],
                        borderColor: '#3b82f6', // Blue
                        backgroundColor: gradient,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Proiezione IA (94% Conf.)',
                        data: [null, null, null, null, 24500, 26200, 28500, 31000, 34500, 38000],
                        borderColor: '#10b981', // Emerald
                        borderDash: [5, 5],
                        borderWidth: 2,
                        tension: 0.4,
                        spanGaps: true,
                        pointRadius: 4
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

    // Initialize Transactions DataTable
    const transTable = document.getElementById('example');
    if (transTable) {
        $(transTable).DataTable({
            language: {
                processing: "Elaborazione...",
                search: "Cerca:",
                lengthMenu: "Visualizza _MENU_ elementi",
                info: "Vista da _START_ a _END_ di _TOTAL_ elementi",
                infoEmpty: "Vista da 0 a 0 di 0 elementi",
                infoFiltered: "(filtrati da _MAX_ elementi totali)",
                infoPostFix: "",
                loadingRecords: "Caricamento...",
                zeroRecords: "La ricerca non ha portato alcun risultato.",
                emptyTable: "Nessun dato presente nella tabella",
                paginate: {
                    first: "Inizio",
                    previous: "Precedente",
                    next: "Successivo",
                    last: "Fine"
                },
                aria: {
                    sortAscending: ": attiva per ordinare la colonna in ordine crescente",
                    sortDescending: ": attiva per ordinare la colonna in ordine decrescente"
                }
            },
            paging: true,
            searching: true,
            ordering: true,
            info: true,
            lengthChange: false,
            pageLength: 5, // Show 5 per page by default for card layout
            order: [[0, 'asc']]
        });
    }
});
