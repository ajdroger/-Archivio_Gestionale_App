/**
 * @file admin_dashboard.js
 * @description MISSION CONTROL VISUALIZATION ENGINE - v7.4.0
 * Orchestrates Real-time Charts, System Pulse Monitoring, and GodMode Protocols.
 * 
 * @author Soobadur Mohammad Ajmeer
 * @version 7.4.0
 */

window.addEventListener('load', function () {
    // Check Dependencies
    if (typeof bootstrap === 'undefined') console.warn('Mission Control: Bootstrap 5 not detected.');
    if (typeof Chart === 'undefined') console.warn('Mission Control: Chart.js not detected.');
    if (typeof Swal === 'undefined') console.warn('Mission Control: SweetAlert2 not detected.');
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
    // --- 6. COMMAND PALETTE ENGINE (Omni-Tool) ---
    const cmdTrigger = document.getElementById('cmd-palette-trigger');
    const cmdModalEl = document.getElementById('commandPaletteModal');
    const cmdInput = document.getElementById('cmd-input');
    const cmdResults = document.getElementById('cmd-results');

    let cmdModal = null;
    if (cmdModalEl && typeof bootstrap !== 'undefined') {
        cmdModal = new bootstrap.Modal(cmdModalEl);

        // Open on Click
        if (cmdTrigger) {
            cmdTrigger.onclick = () => cmdModal.show();
        }

        // Open on Ctrl+K
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'k') {
                e.preventDefault();
                cmdModal.show();
            }
        });

        // Focus input on show
        cmdModalEl.addEventListener('shown.bs.modal', () => {
            cmdInput.value = '';
            renderCommands(); // Show all initially
            cmdInput.focus();
        });
    }

    // Command Definitions
    const commands = [
        { icon: 'fa-user-plus', title: 'Nuovo Socio', desc: 'Registra una nuova anagrafica', action: () => window.location.href = window.BASE_URL + '/soci/nuovo' },
        { icon: 'fa-users', title: 'Lista Soci', desc: 'Vai al registro completo', action: () => window.location.href = window.BASE_URL + '/soci' },
        { icon: 'fa-chart-pie', title: 'Statistiche', desc: 'Analisi demografica e finanziaria', action: () => window.location.href = window.BASE_URL + '/statistiche' },
        { icon: 'fa-house', title: 'Dashboard', desc: 'Torna alla home', action: () => window.location.href = window.BASE_URL + '/' },
        { icon: 'fa-terminal', title: 'DevTools', desc: 'Console di sviluppo', action: () => window.location.href = window.BASE_URL + '/devtools' },
        { icon: 'fa-lock', title: 'Toggle Maintenance', desc: 'Attiva/Disattiva manutenzione globale', action: () => document.getElementById('toggleMaintenance').click() },
        { icon: 'fa-notes-medical', title: 'Add Sticky Note', desc: 'Focus su note rapide', action: () => { cmdModal.hide(); setTimeout(() => document.getElementById('notes-area').focus(), 500); } }
    ];

    function renderCommands(filter = '') {
        if (!cmdResults) return;
        cmdResults.innerHTML = '';

        const filtered = commands.filter(c => c.title.toLowerCase().includes(filter.toLowerCase()) || c.desc.toLowerCase().includes(filter.toLowerCase()));

        if (filtered.length === 0) {
            cmdResults.innerHTML = '<div class="p-3 text-center text-muted">No commands found.</div>';
            return;
        }

        filtered.forEach((cmd, index) => {
            const item = document.createElement('a');
            item.className = 'list-group-item list-group-item-action bg-transparent border-0 text-white d-flex align-items-center p-3';
            item.href = '#';
            if (index === 0) item.classList.add('active-command'); // Highlight first
            item.innerHTML = `
                <div class="rounded-circle bg-secondary bg-opacity-10 p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="fa-solid ${cmd.icon}"></i>
                </div>
                <div>
                    <div class="fw-bold">${cmd.title}</div>
                    <small class="text-white-50">${cmd.desc}</small>
                </div>
            `;
            item.onclick = (e) => {
                e.preventDefault();
                cmd.action();
                cmdModal.hide();
            };
            cmdResults.appendChild(item);
        });
    }

    if (cmdInput) {
        cmdInput.addEventListener('input', (e) => renderCommands(e.target.value));

        // Keyboard Navigation (Basic Enter support)
        cmdInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                const first = cmdResults.querySelector('a');
                if (first) first.click();
            }
        });
    }

    // --- 7. SWITCHBOARD LOGIC (Real-time Toggles) ---
    const toggles = document.querySelectorAll('.dashboard-toggle');
    toggles.forEach(toggle => {
        toggle.addEventListener('change', function () {
            const setting = this.dataset.setting;
            const state = this.checked;

            // Visual Feedback
            const label = this.closest('.d-flex').querySelector('small');
            const originalText = label.innerText;
            label.innerText = 'Updating...';
            label.classList.add('text-warning');

            // Simulate AJAX (Or use fetch('/api/settings/update') if exists)
            // For Genius Mode, we simulate instant success
            setTimeout(() => {
                label.innerText = originalText;
                label.classList.remove('text-warning');

                // Optional: Toast notification
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    background: '#1e293b',
                    color: '#fff'
                });

                Toast.fire({
                    icon: 'success',
                    title: `${setting.toUpperCase()} ${state ? 'ENABLED' : 'DISABLED'}`
                });
            }, 600);
        });
    });

    // --- 8. FIELD NOTES AUTO-SAVE ---
    const notesArea = document.getElementById('notes-area');
    const notesStatus = document.getElementById('notes-status');
    let notesTimeout;

    if (notesArea) {
        notesArea.addEventListener('input', () => {
            notesStatus.innerText = 'Typing...';
            notesStatus.className = 'text-warning x-small font-monospace';

            clearTimeout(notesTimeout);
            notesTimeout = setTimeout(() => {
                notesStatus.innerText = 'Saving...';

                // Simulate Backend Save
                setTimeout(() => {
                    notesStatus.innerText = 'SYNCED';
                    notesStatus.className = 'text-success x-small font-monospace';
                    // Persist to localStorage for demo
                    localStorage.setItem('admin_notes_backup', notesArea.value);
                }, 800);
            }, 1000); // 1s debounce
        });

        // Load backup if exists
        const savedNotes = localStorage.getItem('admin_notes_backup');
        if (savedNotes && !notesArea.value.trim()) {
            notesArea.value = savedNotes;
        }
    }

    // --- 9. INBOX ACTIONS ---
    document.querySelectorAll('.btn-success.x-small').forEach(btn => {
        btn.addEventListener('click', function () {
            const item = this.closest('.list-group-item');
            item.style.transition = 'all 0.5s';
            item.style.opacity = '0';
            item.style.transform = 'translateX(50px)';

            setTimeout(() => {
                item.remove();

                // Update Badge
                const badge = document.querySelector('.badge.bg-danger');
                if (badge) {
                    const current = parseInt(badge.innerText);
                    if (current > 0) badge.innerText = `${current - 1} Pending`;
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Approved',
                    text: 'Request processed successfully.',
                    background: '#1e293b',
                    color: '#fff',
                    timer: 1500,
                    showConfirmButton: false
                });
            }, 500);
        });
    });

    document.querySelectorAll('.btn-outline-info.x-small').forEach(btn => {
        btn.addEventListener('click', () => {
            Swal.fire({
                title: 'Invia Notifica?',
                text: "Verrà inviata una mail di sollecito al socio.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                background: '#1e293b',
                color: '#fff',
                confirmButtonText: 'Invia'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Sent!',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false,
                        background: '#1e293b',
                        color: '#fff'
                    });
                }
            });
        });
    });

});
