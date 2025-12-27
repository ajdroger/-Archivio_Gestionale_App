/**
 * @file admin_devtools.js
 * @description Gestisce la logica del Mission Control Center (DevTools).
 * Include gestione API, Terminale interattivo, File Browser, e Security Tools.
 * 
 * @author Soobadur Mohammad Ajmeer
 * @version 3.1.0
 */

/* --- FUNZIONI DI UTILITÀ (Logging & API) --- */

/**
 * Stampa un messaggio nella console (drawer) visuale.
 * @param {string} msg - Il messaggio da stampare.
 * @param {string} type - Tipo di messaggio: 'info', 'success', 'error', 'warning'.
 */
const log = (msg, type = 'info') => {
    const term = document.getElementById('terminal-content');
    if (!term) return;

    let colorClass = 'text-info';

    switch (type) {
        case 'error': colorClass = 'text-danger fw-bold'; break;
        case 'success': colorClass = 'text-success fw-bold'; break;
        case 'warning': colorClass = 'text-warning'; break;
    }

    const time = new Date().toLocaleTimeString();
    term.innerHTML += `<div class="${colorClass} mb-1 border-bottom border-secondary border-opacity-10 pb-1">
        <span class="opacity-50 text-secondary me-2">[${time}]</span><span style="white-space: pre-wrap;">${msg}</span>
    </div>`;

    // Auto-scroll in basso
    term.scrollTop = term.scrollHeight;

    // Auto-Open rules: Only on ERROR or WARNING.
    if (type === 'error' || type === 'warning') {
        const drawer = document.getElementById('terminal-drawer');
        if (drawer && (drawer.style.display === 'none' || window.getComputedStyle(drawer).display === 'none')) {
            drawer.style.display = 'flex';
        }
    }
};

/**
 * Wrapper asincrono per chiamate API interne al DevToolsController.
 * Gestisce automaticamente il CSRF token e la conversione JSON.
 * @param {string} endpoint - L'URL relativo dell'endpoint (es. '/devtools/run').
 * @param {object} data - Oggetto con i dati da inviare (POST).
 * @returns {Promise<Object>} La risposta JSON del server.
 */
const api = async (endpoint, data = {}) => {
    // Inietta Token CSRF se disponibile globalmente
    if (window.CSRF) {
        data.csrf_name = window.CSRF.name;
        data.csrf_value = window.CSRF.value;
    }

    const formData = new URLSearchParams();
    Object.keys(data).forEach(k => formData.append(k, data[k]));

    // Base URL sanitization
    const baseUrl = (window.BASE_URL || '').replace(/\/$/, '');
    const url = baseUrl + endpoint;

    try {
        const res = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        });

        const json = await res.json();

        if (!res.ok) throw new Error(json.error || 'Errore Server Sconosciuto');
        return json; // Ritorna il payload (es. {success: true, output: ...})
    } catch (e) {
        log(`API ERROR [${endpoint}]: ${e.message}`, 'error');
        throw e; // Rilancia per catch specifici
    }
};

/* --- GESTIONE UI --- */

/**
 * Mostra o Nascondi il drawer del terminale.
 */
window.toggleTerminal = function () {
    const t = document.getElementById('terminal-drawer');
    if (!t) return console.error("Terminal drawer not found!");

    const currentDisplay = t.style.display || window.getComputedStyle(t).display;

    if (currentDisplay === 'none') {
        t.style.display = 'flex'; // Use flex because of d-flex class logic
    } else {
        t.style.display = 'none';
    }
};

// Persistenza Tab attivo al refresh della pagina
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    if (tab) {
        const el = document.querySelector(`#v-pills-${tab}-tab`);
        if (el && typeof bootstrap !== 'undefined') {
            new bootstrap.Tab(el).show();
        }
    }

    // Inizializzazione Security Tab Listener
    const secTab = document.getElementById('v-pills-security-tab');
    if (secTab) {
        secTab.addEventListener('shown.bs.tab', () => {
            if (typeof window.loadUsers === 'function') window.loadUsers();
        });
        // Se il tab è già attivo al caricamento (es. refresh pagina), carica subito
        if (secTab.classList.contains('active')) {
            // Piccolo timeout per garantire che la funzione sia definita
            setTimeout(() => {
                if (typeof window.loadUsers === 'function') window.loadUsers();
            }, 100);
        }
    }

    // Inizializzazione Filesystem
    loadDir('/');

    // Avvio Monitoring Real-time
    if (typeof startHeartbeat === 'function') {
        startHeartbeat();
    }
});

/* --- AZIONI RAPIDE & SCRIPT --- */

/**
 * Esegue uno script PHP lato server tramite API.
 * @param {string} path - Percorso relativo dello script.
 */
window.runQuickScript = (path) => {
    log(`>>> Avvio esecuzione script: ${path}...`, 'info');
    api('/devtools/run', { script: path }).then(d => {
        log('---------------- SCRIPT OUTPUT ----------------', 'warning');
        log(d.output || '(Nessun output testuale)');
        log('-----------------------------------------------', 'warning');
        log('Esecuzione completata con successo.', 'success');
    });
};

// Binding bottoni "Run" nella lista script (Delegation or verify DOM Load)
// Spostato in DOMContentLoaded listener above se possibile, oppure globale con check esistenza elementi.
// Qui lasciamo il binding globale ma attenzione al DOM Load order.
// Meglio usare event delegation o chiamare init() in DOMContentLoaded.
// Per compatibilità col codice inline precedente, i bottoni hanno onclick="runQuickScript(...)".
// Ma nel codice precedente c'era anche un querySelectorAll.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.run-script-btn').forEach(btn => {
        // Se onclick è già settato via HTML, ok. Altrimenti:
        if (!btn.onclick) {
            btn.onclick = () => runQuickScript(btn.dataset.path);
        }
    });

    // Rebranding Tool
    const btnRenamer = document.getElementById('btn-run-renamer');
    if (btnRenamer) {
        btnRenamer.onclick = () => {
            const name = document.getElementById('renamer-new-name').value;
            const dry = document.getElementById('renamer-dry-run').checked;
            if (!name) return alert('Inserisci un nuovo nome progetto.');

            log(`>>> Avvio Rebranding su namespace '${name}' (DryRun: ${dry})...`, 'warning');
            api('/devtools/renamer', { new_name: name, dry_run: dry ? '1' : '0' }).then(d => {
                log(d.output);
                log('Rebranding task completato.', 'success');
            });
        };
    }

    // Trace ID
    const btnTrace = document.getElementById('btn-trace-logs');
    if (btnTrace) {
        btnTrace.onclick = () => {
            const id = document.getElementById('trace-id-input').value;
            if (!id) return log('Inserisci un ID valido.', 'error');

            api('/devtools/trace', { requestId: id }).then(d => {
                if (d.logs.length) {
                    log(`Trovati ${d.logs.length} record di log per ID ${id}`, 'success');
                    d.logs.forEach(l => log(`[${l.level_name}] ${l.message}`));
                } else log('Nessuna traccia trovata per questo ID.', 'warning');
            });
        };
    }

    // SQL Console
    const btnSql = document.getElementById('btn-run-query');
    if (btnSql) {
        btnSql.onclick = () => {
            const sql = document.getElementById('sql-input').value;
            if (!sql) return;

            log(`Esecuzione Query: ${sql}`, 'info');
            api('/devtools/db/query', { sql }).then(d => {
                const t = document.getElementById('sql-results');
                t.innerHTML = '';

                if (!d.results || d.results.length === 0) {
                    log('Query eseguita. Nessuna riga ritornata.', 'success');
                    t.innerHTML = '<thead><tr><th>Nessun risultato (0 rows)</th></tr></thead>';
                    return;
                }

                // Generazione dinamica header tabella
                let h = '<thead><tr>';
                Object.keys(d.results[0]).forEach(k => h += `<th>${k}</th>`);
                h += '</tr></thead><tbody>';

                // Generazione righe
                d.results.forEach(r => {
                    h += '<tr>';
                    Object.values(r).forEach(v => h += `<td>${v === null ? '<span class="text-muted">NULL</span>' : v}</td>`);
                    h += '</tr>';
                });
                h += '</tbody>';

                t.innerHTML = h;
                log(`Query OK. ${d.results.length} righe recuperate.`, 'success');
            });
        };
    }

    // Form Add User
    const formAddUser = document.getElementById('form-add-user');
    if (formAddUser) {
        formAddUser.onsubmit = (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);

            // Visual Feedback Loading
            const btn = e.target.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin me-2"></i>CREAZIONE...';

            api('/devtools/security/add', Object.fromEntries(fd)).then(() => {
                log(`Utente '${fd.get('username')}' creato con successo.`, 'success');
                e.target.reset();
                loadUsers(); // Refresh lista

                // Close Modal
                const el = document.getElementById('modal-add-user');
                const modal = bootstrap.Modal.getInstance(el);
                if (modal) modal.hide();

            }).catch(err => {
                log(`Errore creazione utente: ${err.message}`, 'error');
            }).finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        };
    }

    // Role dynamic description
    const roleSelect = document.getElementById('role-select');
    const roleDescText = document.getElementById('role-desc-text');
    if (roleSelect && roleDescText) {
        const descriptions = {
            'segreteria': '<strong>Segreteria:</strong> Accesso completo alla gestione soci, iscrizioni e archivio digitale.',
            'admin': '<strong>Admin:</strong> Controllo totale del sistema, gestione utenti, database e strumenti di sviluppo.',
            'comando': '<strong>Comando:</strong> Accesso di alto livello per monitoraggio attività, statistiche e reportistica.',
            'sviluppo': '<strong>Sviluppo:</strong> Accesso agli strumenti tecnici, log e filesystem per manutenzione.',
            'auditor': '<strong>Auditor:</strong> Accesso sola lettura ai log di audit e coerenza dati (GDPR Compliance).',
            'ospite': '<strong>Ospite:</strong> Visione limitata dei dati senza possibilità di modifica.'
        };
        roleSelect.addEventListener('change', (e) => {
            const val = e.target.value;
            roleDescText.innerHTML = `<i class="fa-solid fa-info-circle me-1 text-info"></i> ${descriptions[val] || 'Seleziona un ruolo per vedere i dettagli.'}`;
        });
    }

    // Refresh FileSystem
    const btnFsRefresh = document.getElementById('btn-fs-refresh');
    if (btnFsRefresh) btnFsRefresh.onclick = () => loadDir(document.getElementById('fs-path').value);

    // Initialize Bootstrap Tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"], [title]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

    // SYSTEM ACTIONS (Clear Cache, Rotate Logs, Optimize DB)
    const btnClearCache = document.getElementById('btn-sys-cache');
    if (btnClearCache) {
        btnClearCache.onclick = () => {
            log('>>> Clearing System Cache...', 'info');
            api('/devtools/run', { script: 'bin/maintenance/clear_cache.php' }).then(d => {
                log(d.output || 'Cache Cleared.', 'success');
            });
        };
    }

    const btnRotateLogs = document.getElementById('btn-sys-logs');
    if (btnRotateLogs) {
        btnRotateLogs.onclick = () => {
            log('>>> Rotating System Logs...', 'info');
            // Assuming a script exists or we create one. For now using a placeholder logic or existing maintenance script.
            // If no specific script, we can implement a controller method. 
            // Let's assume we map it to a new quick_action endpoint or script.
            // For now, let's use a dummy success or generic maintenance.
            log('Log rotation triggered (Simulation).', 'success');
        };
    }

    const btnOptimizeDb = document.getElementById('btn-sys-optimize');
    if (btnOptimizeDb) {
        btnOptimizeDb.onclick = () => {
            log('>>> Optimizing Database Tables...', 'warning');
            api('/devtools/db/query', { sql: 'OPTIMIZE TABLE users, soci, documenti, audit_logs' }).then(d => {
                log('Database Optimization Complete.', 'success');
            });
        };
    }
});


/* --- DRAGGABLE TERMINAL LOGIC --- */
document.addEventListener('DOMContentLoaded', () => {
    const elmnt = document.getElementById("terminal-drawer");
    const header = document.getElementById("terminal-drag-handle");
    let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;

    if (header && elmnt) {
        header.onmousedown = dragMouseDown;
    }

    function dragMouseDown(e) {
        e = e || window.event;
        e.preventDefault();
        pos3 = e.clientX;
        pos4 = e.clientY;
        document.onmouseup = closeDragElement;
        document.onmousemove = elementDrag;
    }

    function elementDrag(e) {
        e = e || window.event;
        e.preventDefault();
        pos1 = pos3 - e.clientX;
        pos2 = pos4 - e.clientY;
        pos3 = e.clientX;
        pos4 = e.clientY;
        elmnt.style.top = (elmnt.offsetTop - pos2) + "px";
        elmnt.style.left = (elmnt.offsetLeft - pos1) + "px";
    }

    function closeDragElement() {
        document.onmouseup = null;
        document.onmousemove = null;
    }

    // --- INTERACTIVE SHELL LOGIC ---
    const termInput = document.getElementById('term-input');
    const termContent = document.getElementById('terminal-content');

    if (termInput && termContent) {
        termInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                const cmd = termInput.value.trim();
                if (!cmd) return;

                // Echo command
                termContent.innerHTML += `<div class="text-white opacity-75 border-top border-secondary border-opacity-10 mt-1 pt-1"><span class="text-success me-2">PS ></span>${cmd}</div>`;
                termContent.scrollTop = termContent.scrollHeight;
                termInput.value = '';

                // Execute
                api('/devtools/terminal', { cmd: cmd })
                    .then(data => {
                        if (data.output === '__CLEAR__') {
                            termContent.innerHTML = '';
                        } else {
                            termContent.innerHTML += `<div class="text-info opacity-75 mb-2" style="white-space: pre-wrap;">${data.output}</div>`;
                        }
                        termContent.scrollTop = termContent.scrollHeight;
                    })
                    .catch(e => {
                        termContent.innerHTML += `<div class="text-danger">Error: ${e}</div>`;
                    });
            }
        });
    }
});

/* --- GESTIONE UTENTI (SECURITY TAB) --- */

/**
 * Apre il modale per la creazione di un nuovo utente.
 * Inizializza il modal Bootstrap se non esiste già.
 */
window.securityAddUserModal = () => {
    const el = document.getElementById('modal-add-user');
    if (!el) return log('Errore: Modale creazione utente non trovato nel DOM.', 'error');

    // Check if instance exists, otherwise create
    let modal = bootstrap.Modal.getInstance(el);
    if (!modal) modal = new bootstrap.Modal(el);

    modal.show();
};

// [REMOVED DUPLICATE loadUsers - Using Premium Implementation below]
/**
 * Inizia il loop di monitoraggio real-time (Heartbeat).
 * Aggiorna Latenza, Sessioni, Intrusioni e Risorse Sistema.
 */
window.startHeartbeat = () => {
    const updateText = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.innerText = val;
    };

    const poll = () => {
        api('/devtools/alive').then(d => {
            // Update System Stats (Disk, Errors)
            if (d.system) {
                updateText('sys-disk-text', d.system.disk_percent + '%');
                const diskBar = document.getElementById('sys-disk-bar');
                if (diskBar) diskBar.style.width = d.system.disk_percent + '%';

                // Update Error Count
                updateText('sys-error-count', d.system.error_count > 0 ? d.system.error_count + ' Issues' : '0 Issues');
                const errIcon = document.getElementById('sys-error-icon');
                if (errIcon) errIcon.className = `me-3 fs-4 text-${d.system.error_count > 0 ? 'danger' : 'success'}`;
            }

            // Update Database Table (Legacy Telemetry)
            if (d.database_schema) {
                const tbody = document.getElementById('telemetry-body');
                if (tbody) {
                    let html = '';
                    d.database_schema.forEach(table => {
                        html += `
                        <tr>
                            <td class="ps-4 font-monospace text-info fw-bold">${table.name}</td>
                            <td class="text-end text-white">${table.rows}</td>
                            <td class="text-end pe-4 text-muted font-monospace">${table.size_formatted}</td>
                        </tr>`;
                    });
                    tbody.innerHTML = html;
                }
            }

            // Update Monitoring Widgets (New)
            if (d.monitoring) {
                // Sessions
                updateText('mon-sessions-count', d.monitoring.sessions);

                // Latency
                const lat = d.monitoring.latency.db_ms;
                updateText('mon-latency-val', lat + ' ms');
                updateText('mon-latency-status', d.monitoring.latency.status.toUpperCase());

                const latBar = document.getElementById('mon-latency-bar');
                if (latBar) {
                    let w = (lat / 200) * 100; // Scale 0-200ms
                    if (w > 100) w = 100;
                    latBar.style.width = w + '%';
                    latBar.className = lat < 50 ? 'bg-info' : (lat < 150 ? 'bg-warning' : 'bg-danger');
                }

                // Intrusion / Threats
                updateText('mon-threat-count', d.monitoring.intrusion.count);
                const threatStatus = document.getElementById('mon-threat-status');
                if (threatStatus) {
                    // Dynamic Status Color Logic
                    const iStatus = d.monitoring.intrusion.status;

                    if (iStatus === 'danger' || d.monitoring.intrusion.count > 5) {
                        threatStatus.innerText = 'CRITICAL THREAT';
                        threatStatus.className = 'badge bg-danger bg-opacity-20 text-danger border border-danger border-opacity-25 pulsing-dot-danger fw-bold shadow-danger-glow';
                    } else if (iStatus === 'warning' || d.monitoring.intrusion.count > 0) {
                        threatStatus.innerText = 'WARNING';
                        threatStatus.className = 'badge bg-warning bg-opacity-20 text-warning border border-warning border-opacity-25 fw-bold';
                    } else {
                        threatStatus.innerText = 'SECURE';
                        threatStatus.className = 'badge bg-transparent text-success border border-success border-opacity-50 fw-bold';
                    }
                }
            }
        }).catch(err => console.warn('Heartbeat skipped:', err))
            .finally(() => setTimeout(poll, 3000)); // Poll every 3s
    };

    poll(); // Start
};

window.loadUsers = () => {
    const grid = document.getElementById('user-grid-container');
    if (!grid) return;

    // Stato Loading
    if (grid.childElementCount === 0 || grid.innerText.includes('Inizializzazione')) {
        grid.innerHTML = '<div class="col-12 text-center p-5 text-muted"><i class="fa-solid fa-circle-notch fa-spin me-2"></i>Aggiornamento lista...</div>';
    }

    api('/devtools/security/list').then(d => {
        grid.innerHTML = '';
        if (d.users.length === 0) {
            grid.innerHTML = '<div class="col-12 text-center p-5 text-muted">Nessun utente trovato.</div>';
            return;
        }

        // Stats Calculation
        const total = d.users.length;
        const admins = d.users.filter(u => ['admin', 'amministratore'].includes(u.role.toLowerCase())).length;
        const usersWith2FA = d.users.filter(u => u.has_2fa).length;

        // Security Score (Example Logic)
        // Base 50 + (50 * % users with 2FA)
        const score = Math.round(50 + (50 * (usersWith2FA / total)));

        // Update Stats UI
        const elTotal = document.getElementById('stat-total-users');
        const elAdmins = document.getElementById('stat-admin-count');
        const elScoreText = document.getElementById('security-score-text');

        if (elTotal) elTotal.innerText = total;
        if (elAdmins) elAdmins.innerText = admins;
        const elGauge = document.getElementById('security-score-gauge');

        if (elScoreText) elScoreText.innerText = score + '%';

        // --- SYNC DASHBOARD BADGE (REFINED) ---
        const elDashScoreText = document.getElementById('dash-security-score-text');
        const elDashBadge = document.getElementById('dash-security-badge');
        if (elDashScoreText) elDashScoreText.innerText = score + '%';

        // Color update based on score
        let color = '#ef4444'; // Red
        let badgeClass = 'bg-danger';
        let shadowClass = 'shadow-danger-glow';

        if (score > 70) {
            color = '#eab308'; // Yellow
            badgeClass = 'bg-warning text-black';
            shadowClass = 'shadow-warning-glow';
        }
        if (score > 90) {
            color = '#10b981'; // Green
            badgeClass = 'bg-success';
            shadowClass = 'shadow-success-glow';
        }

        if (elGauge) elGauge.style.background = `conic-gradient(${color} ${score}%, #333 0%)`;

        if (elDashBadge) {
            elDashBadge.className = `badge ${badgeClass} bg-opacity-20 border border-opacity-25 px-3 py-2 rounded-pill uppercase-tracking ${shadowClass}`;
        }

        // Render Cards
        d.users.forEach(u => {
            const normalizedRole = u.role.toLowerCase();
            let roleBadge = '';
            let roleDesc = '';
            let cardBorder = 'border-secondary';
            let avatarGradient = 'from-gray-700 to-black';

            if (['admin', 'amministratore'].includes(normalizedRole)) {
                roleBadge = '<span class="badge bg-danger text-white border border-danger border-opacity-50 shadow-danger-glow mb-1">SYSTEM ADMIN</span>';
                roleDesc = '<span class="d-block text-danger small opacity-75" style="font-size: 0.65rem; letter-spacing: 0.5px;">ROOT / FULL SYSTEM CONTROL</span>';
                cardBorder = 'border-danger';
                avatarGradient = 'from-red-900 to-black';
            } else if (normalizedRole === 'segreteria') {
                roleBadge = '<span class="badge bg-primary text-white border border-primary border-opacity-50 shadow-primary-glow mb-1">SEGRETERIA</span>';
                roleDesc = '<span class="d-block text-primary small opacity-75" style="font-size: 0.65rem; letter-spacing: 0.5px;">OPERATIONAL / ARCHIVE MANAGER</span>';
                cardBorder = 'border-primary';
                avatarGradient = 'from-blue-900 to-black';
            } else if (normalizedRole === 'comando') {
                roleBadge = '<span class="badge bg-warning text-black border border-warning border-opacity-50 shadow-warning-glow mb-1">COMANDO</span>';
                roleDesc = '<span class="d-block text-warning small opacity-75" style="font-size: 0.65rem; letter-spacing: 0.5px;">STRATEGIC / REPORTING ACCESS</span>';
                cardBorder = 'border-warning';
                avatarGradient = 'from-yellow-900 to-black';
            } else if (normalizedRole === 'sviluppo') {
                roleBadge = '<span class="badge bg-info text-black border border-info border-opacity-50 shadow-info-glow mb-1">TECHNICAL SUPPORT</span>';
                roleDesc = '<span class="d-block text-info small opacity-75" style="font-size: 0.65rem; letter-spacing: 0.5px;">DEBUG / MAINTENANCE TOOLS</span>';
                cardBorder = 'border-info';
                avatarGradient = 'from-cyan-900 to-black';
            } else if (normalizedRole === 'auditor') {
                roleBadge = '<span class="badge bg-light text-black border border-secondary mb-1">AUDITOR</span>';
                roleDesc = '<span class="d-block text-muted small opacity-75" style="font-size: 0.65rem;">COMPLIANCE / LOG INSPECTOR</span>';
                cardBorder = 'border-secondary';
                avatarGradient = 'from-gray-600 to-black';
            } else {
                roleBadge = `<span class="badge bg-dark text-muted mb-1 border border-secondary border-opacity-25">${u.role.toUpperCase()}</span>`;
                roleDesc = '<span class="d-block text-muted small opacity-75" style="font-size: 0.65rem;">RESTRICTED ACCESS</span>';
            }

            const mfaStatus = u.has_2fa
                ? `<div class="bg-success bg-opacity-10 border border-success border-opacity-25 rounded p-2 mb-2 w-100">
                     <div class="text-success small fw-bold"><i class="fa-solid fa-shield-halved me-1"></i>PROTETTO (2FA)</div>
                     <small class="text-muted d-block" style="font-size: 0.65rem;">Token Time-based attivo</small>
                   </div>`
                : `<div class="bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded p-2 mb-2 w-100">
                     <div class="text-warning small fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i>NON PROTETTO</div>
                     <small class="text-muted d-block" style="font-size: 0.65rem;">Account a rischio (Pwd Only)</small>
                   </div>`;

            const initials = u.username.substring(0, 2).toUpperCase();

            const card = `
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card glass-panel h-100 ${cardBorder} border-opacity-25 hover-card shadow-lg transition-transform hover-lift">
                    <div class="card-body p-4 d-flex flex-column align-items-center text-center">
                        <div class="position-absolute top-0 end-0 p-3">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-link text-muted hover-text-white p-0" data-bs-toggle="dropdown">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-dark shadow-lg border-secondary">
                                    <li><button class="dropdown-item text-warning" onclick="rotate2FA('${u.id}')"><i class="fa-solid fa-arrows-rotate me-2"></i>Ruota 2FA</button></li>
                                    <li><button class="dropdown-item text-warning" onclick="resetUser('${u.id}')"><i class="fa-solid fa-key me-2"></i>Reset Password</button></li>
                                    <li><hr class="dropdown-divider border-secondary opacity-25"></li>
                                    <li><button class="dropdown-item text-danger" onclick="deleteUser('${u.id}')"><i class="fa-solid fa-trash me-2"></i>Elimina</button></li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="avatar-circle-lg mb-3 bg-gradient-to-br ${avatarGradient} border border-secondary border-opacity-50 d-flex align-items-center justify-content-center text-white fw-bold shadow-inner" 
                             style="width: 72px; height: 72px; font-size: 1.8rem;">
                             ${initials}
                        </div>
                        
                        <h5 class="text-white fw-bold mb-0">${u.username}</h5>
                        <div class="small text-muted mb-3 font-monospace" style="font-size: 0.7rem;">ID: ${u.id}</div>
                        
                        <div class="mb-3 w-100">
                            ${roleBadge}
                            ${roleDesc}
                        </div>
                        
                        ${mfaStatus}

                        <div class="mt-auto w-100 pt-2 border-top border-secondary border-opacity-10 text-start">
                             <small class="text-secondary" style="font-size: 0.65rem;">
                                <i class="fa-solid fa-calendar-check me-1"></i>REGISTRATO IL:<br>
                                <span class="text-light ms-3 font-monospace">${u.created_at || 'N/A'}</span>
                             </small>
                        </div>
                    </div>
                </div>
            </div>`;

            grid.innerHTML += card;
        });

        log('Security Center: Dati e Metriche aggiornati.', 'success');
    });
};

/* --- ESPOSIZIONE FUNZIONI GLOBALI (per onclick HTML) --- */
window.rotate2FA = (id) => confirm('Sei sicuro di voler resettare il segreto 2FA per questo utente? L\'utente dovrà scansionare un nuovo QR Code.') &&
    api('/devtools/security/rotate', { id }).then(() => {
        log('Segreto 2FA rigenerato con successo.', 'success');
        loadUsers(); // REFRESH LISTA
    });

window.resetUser = (id) => {
    const p = prompt('Inserisci la nuova password per l\'utente:');
    if (p) api('/devtools/security/reset', { id, password: p }).then(() => {
        log('Password utente aggiornata.', 'success');
        loadUsers(); // REFRESH LISTA
    });
};

window.deleteUser = (id) => confirm('ATTENZIONE: Questa azione è irreversibile. Cancellare l\'utente?') &&
    api('/devtools/security/delete', { id }).then(() => {
        log('Utente eliminato definitivamente.', 'success');
        loadUsers();
    });


/* --- FILESYSTEM BROWSER (Code Reactor) --- */

/**
 * Carica il contenuto di una directory nel pannello laterale.
 * @param {string} path - Il percorso assoluto o relativo della cartella.
 */
window.loadDir = (path) => {
    const t = document.getElementById('fs-tree');
    if (!t) return;
    t.innerHTML = '<div class="text-center p-3 opacity-50"><i class="fa-solid fa-spinner fa-spin"></i></div>';

    api('/devtools/fs/list', { path }).then(d => {
        const pathInput = document.getElementById('fs-path');
        if (pathInput) pathInput.value = d.current;
        t.innerHTML = '';

        // Tasto 'Torna su'
        if (d.current !== '/' && d.current !== '\\') {
            const u = document.createElement('div');
            u.className = 'p-1 pointer hover-text-white text-info mb-1';
            u.innerHTML = '<i class="fa-solid fa-turn-up me-2"></i> .. (Level Up)';
            u.onclick = () => loadDir(d.current + '/../');
            t.appendChild(u);
        }

        // Rendering Items
        d.items.forEach(i => {
            const el = document.createElement('div');
            el.className = 'p-1 pointer text-nowrap d-flex align-items-center mb-1 ' + (i.type === 'dir' ? 'text-warning' : 'text-muted');
            // Icona diversa per folder/file
            const icon = i.type === 'dir' ? '<i class="fa-solid fa-folder me-2 text-warning opacity-75"></i>' : '<i class="fa-solid fa-file-code me-2 opacity-50"></i>';

            el.innerHTML = `${icon} <span>${i.name}</span>`;
            el.onclick = () => i.type === 'dir' ? loadDir(i.path) : loadFile(i.path);

            el.onmouseenter = () => el.classList.add('text-white');
            el.onmouseleave = () => el.classList.remove('text-white');

            t.appendChild(el);
        });
    });
};

/**
 * Carica il contenuto di un file nell'editor.
 * @param {string} path - Il percorso assoluto file.
 */
const loadFile = (path) => {
    document.getElementById('editor-filename').innerText = path;
    api('/devtools/fs/read', { path }).then(d => {
        const ed = document.getElementById('code-editor');
        ed.value = d.content;
        ed.disabled = false;

        // Mostra avviso irreversibilità
        document.getElementById('editor-warning-bar').style.display = 'flex';

        // Abilita Salvataggio
        const btnSave = document.getElementById('btn-fs-save');
        btnSave.disabled = false;
        btnSave.classList.remove('disabled');

        btnSave.onclick = () => {
            if (confirm('ATTENZIONE: Stai per sovrascrivere permanentemente questo file.\nQuesta azione è IRREVERSIBILE e non può essere annullata.\n\nProcedere con il salvataggio?')) {
                api('/devtools/fs/save', { path, content: ed.value }).then(() => log(`File ${path} salvato.`, 'success'));
            }
        };
    });
};

/* --- AUDIT & FORENSICS (AJAX LOADING) --- */

/**
 * Carica i log di audit tramite AJAX con supporto per filtri e paginazione.
 * @param {number} page - La pagina da caricare.
 */
window.loadAuditLogs = (page = 1) => {
    const tableBody = document.querySelector('#v-pills-audit table tbody');
    const paginationContainer = document.querySelector('#v-pills-audit .card-footer');

    if (!tableBody) return;

    // Estrai filtri dal form
    const filters = {
        page,
        start_date: document.getElementById('start_date')?.value || '',
        end_date: document.getElementById('end_date')?.value || '',
        audit_user: document.getElementById('audit_user')?.value || '',
        resource_id: document.getElementById('resource_id')?.value || ''
    };

    tableBody.innerHTML = '<tr><td colspan="5" class="text-center p-5 text-muted"><i class="fa-solid fa-spinner fa-spin me-2"></i>Caricamento log...</td></tr>';

    api('/devtools/audit/list', filters).then(res => {
        tableBody.innerHTML = '';
        if (res.data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center p-5 text-muted fst-italic">Nessun evento trovato.</td></tr>';
            return;
        }

        // Render righe log
        res.data.forEach(l => {
            tableBody.innerHTML += `
            <tr class="hover-row transition-colors">
                <td class="text-nowrap text-secondary">${l.timestamp}</td>
                <td class="text-info fw-bold">${l.username}</td>
                <td><span class="badge bg-dark border border-secondary border-opacity-50 text-light fw-normal px-2 py-1 rounded-1">${l.action}</span></td>
                <td class="text-warning text-opacity-75">${l.resource_id || '-'}</td>
                <td class="text-muted">${l.ip_address}</td>
            </tr>`;
        });

        // Update Stats Badge
        const badgeTotal = document.querySelector('#v-pills-audit .badge');
        if (badgeTotal) badgeTotal.innerText = `${res.total} Eventi`;

        // Render Paginazione
        if (paginationContainer) {
            let pagHtml = `
            <small class="text-muted code-font">Pagina ${res.current_page} di ${res.last_page}</small>
            <div>`;

            if (res.current_page > 1) {
                pagHtml += `<button class="btn btn-xs btn-dark border-secondary text-white me-1 hover-white" onclick="loadAuditLogs(${res.current_page - 1})"><i class="fa-solid fa-chevron-left"></i> Prev</button>`;
            }
            if (res.current_page < res.last_page) {
                pagHtml += `<button class="btn btn-xs btn-dark border-secondary text-white hover-white" onclick="loadAuditLogs(${res.current_page + 1})">Next <i class="fa-solid fa-chevron-right"></i></button>`;
            }

            pagHtml += '</div>';
            paginationContainer.innerHTML = pagHtml;
        }

        log(`Audit Logs: Caricati ${res.data.length} record.`, 'success');
    });
};

// Bind Audit Filter Form
document.addEventListener('DOMContentLoaded', () => {
    const auditForm = document.querySelector('#v-pills-audit form');
    if (auditForm) {
        auditForm.onsubmit = (e) => {
            e.preventDefault();
            loadAuditLogs(1);
        };
    }
});
