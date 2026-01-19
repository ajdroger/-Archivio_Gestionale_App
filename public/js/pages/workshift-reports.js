document.addEventListener('DOMContentLoaded', () => {
    if (typeof REPORT_DATA === 'undefined') {
        console.error('REPORT_DATA is missing');
        return;
    }

    initOvertimeChart();
    initLaborCostChart();

    // AI Button Handler
    const btn = document.getElementById('btnApplyAiSuggestion');
    if (btn) {
        btn.addEventListener('click', async () => {
            // Disable button
            btn.disabled = true;
            btn.innerHTML = '<i data-feather="loader" class="w-3 h-3 animate-spin"></i> Applicando...';
            feather.replace();

            const targetDate = btn.dataset.date;
            const shiftTime = btn.dataset.time;

            try {
                const res = await fetch('/MCAG_Militare-Civile-Archivio-Gestionale/public/workshift/api/apply-suggestion', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ target_date: targetDate, shift_time: shiftTime })
                });
                const data = await res.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Ottimizzazione Applicata!',
                        text: data.message || 'Turno Jolly creato con successo.',
                        confirmButtonColor: '#4f46e5'
                    });
                    btn.innerHTML = 'Applicato <i data-feather="check" class="w-3 h-3"></i>';
                    btn.classList.add('text-green-400');
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Errore',
                    text: error.message || 'Impossibile applicare il suggerimento.',
                    confirmButtonColor: '#ef4444'
                });
                btn.disabled = false;
                btn.innerHTML = 'RIPROVA <i data-feather="refresh-cw" class="w-3 h-3"></i>';
                feather.replace();
            }
        });
    }

    // Refresh AI Button Handler
    const btnRefresh = document.getElementById('btnRefreshAiSuggestion');
    if (btnRefresh) {
        btnRefresh.addEventListener('click', async () => {
            // Animate icon
            btnRefresh.querySelector('i').classList.add('animate-spin');

            try {
                const res = await fetch('/MCAG_Militare-Civile-Archivio-Gestionale/public/workshift/api/ai-suggestion');
                const data = await res.json();

                if (data.success && data.suggestion) {
                    // Update Message
                    document.querySelector('.text-indigo-200\\/70.text-sm.leading-relaxed.max-w-3xl').innerHTML = data.suggestion.message;

                    // Update Apply Button Data
                    const btnApply = document.getElementById('btnApplyAiSuggestion');
                    if (btnApply) {
                        btnApply.dataset.date = data.suggestion.target_date;
                        btnApply.dataset.time = data.suggestion.shift_time;
                    }

                    // Toast for visual confirmation
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                        }
                    });

                    Toast.fire({
                        icon: 'info',
                        title: 'Analisi aggiornata'
                    });
                }
            } catch (e) {
                console.error(e);
            } finally {
                // Stop animation
                btnRefresh.querySelector('i').classList.remove('animate-spin');
            }
        });
    }
});

function initOvertimeChart() {
    const ctx = document.getElementById('overtimeChart');
    if (!ctx) return;

    const data = REPORT_DATA.trend;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Ore Lavorate',
                data: data.data,
                borderColor: '#22d3ee', // Cyan-400
                backgroundColor: 'rgba(34, 211, 238, 0.1)',
                borderWidth: 3,
                tension: 0.4, // Smooth curves
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
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleColor: '#cbd5e1',
                    bodyColor: '#fff',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1,
                    padding: 10,
                    displayColors: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.05)',
                        borderDash: [5, 5]
                    },
                    ticks: {
                        color: '#94a3b8'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#94a3b8'
                    }
                }
            }
        }
    });
}

function initLaborCostChart() {
    const ctx = document.getElementById('laborCostChart');
    if (!ctx) return;

    const data = REPORT_DATA.distribution;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.data,
                backgroundColor: [
                    '#6366f1', // Indigo-500
                    '#8b5cf6', // Violet-500
                    '#ec4899', // Pink-500
                    '#06b6d4', // Cyan-500
                    '#10b981'  // Emerald-500
                ],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '75%', // Thinner ring
            plugins: {
                legend: {
                    display: false // Custom legend is in HTML
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            return ` ${context.label}: ${context.raw}`;
                        }
                    },
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    bodyColor: '#fff',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1
                }
            }
        }
    });
}
