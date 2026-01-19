/**
 * @file admin_devtools.js
 * @description Gestisce la logica del Mission Control Center (DevTools).
 * Include gestione API, Terminale interattivo, File Browser, e Security Tools.
 * 
 * @author Soobadur Mohammad Ajmeer
 * @version 3.1.0
 */

/* --- FUNZIONI DI UTILITÀ (Logging & API) --- */

// Flag per impedire l'apertura automatica del terminale al caricamento pagina
let isBooting = true;

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

    // Auto-Open rules: DISABLED by user request. Terminal opens only on click.
    // Auto-Open rules: Only on ERROR (and NOT during boot).
    if (type === 'error' && !isBooting) {
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
 * Legacy wrapper replaced by generic event, kept for compatibility if needed.
 */
window.toggleTerminal = function () {
    const t = document.getElementById('terminal-drawer');
    if (!t) return;

    const currentDisplay = t.style.display || window.getComputedStyle(t).display;

    if (currentDisplay === 'none') {
        t.style.display = 'flex'; // Use flex because of d-flex class logic
    } else {
        t.style.display = 'none';
    }
};

// Persistenza Tab attivo al refresh della pagina
document.addEventListener('DOMContentLoaded', () => {
    // Disable boot flag after 4 seconds (prevents auto-open on init logs)
    setTimeout(() => {
        isBooting = false;
        console.log("DevTools: Boot phase complete. Auto-open enabled for errors.");
    }, 4000);

    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    if (tab) {
        const el = document.querySelector(`#v-pills-${tab}-tab`);
        if (el && typeof bootstrap !== 'undefined') {
            new bootstrap.Tab(el).show();
        }
    }

    // Force load AI Tab on show if empty/spin-only
    const aiTab = document.getElementById('v-pills-ai-tab');
    if (aiTab) {
        aiTab.addEventListener('shown.bs.tab', () => {
            const container = document.getElementById('v-pills-ai-content');
            // If contains spinner (loading state), trigger htmx via native event
            if (container && container.querySelector('.spinner-border')) {
                // Use native dispatch to avoid ReferenceError if htmx global is missing
                const evt = new MouseEvent('click', {
                    bubbles: true,
                    cancelable: true,
                    view: window
                });
                aiTab.dispatchEvent(evt);
            }
        });
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
            'direttore_associazione': '<strong>Direttore:</strong> Accesso completo (Write) e Dashboard intelligence.',
            'collegio_sindacale': '<strong>Collegio Sindacale:</strong> Accesso Intelligence e Audit. Sola lettura Anagrafica.',
            'comando': '<strong>Comando:</strong> Accesso di alto livello per monitoraggio attività, statistiche e reportistica.',
            'ente_universita': '<strong>Università:</strong> Accesso scopo ricerca e analisi demografiche (Stats Only).',
            'ente_sanitario': '<strong>Sanità:</strong> Accesso reportistica sanitaria e monitoraggio (Stats Only).',
            'ente_pubblico': '<strong>P.A. / Sicurezza:</strong> Accesso verifiche istituzionali e statistiche aggregate.',
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

    // Demo Invitation Tool
    const btnSendDemo = document.getElementById('btn-send-demo');
    if (btnSendDemo) {
        btnSendDemo.onclick = () => {
            const email = document.getElementById('demo-email').value;
            const name = document.getElementById('demo-name').value;

            if (!email) return log('Inserisci almeno un indirizzo email.', 'error');

            log(`>>> Sending Demo Invite to ${email}...`, 'info');

            // Visual Feedback
            const originalText = btnSendDemo.innerHTML;
            btnSendDemo.disabled = true;
            btnSendDemo.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>';

            api('/devtools/demo-invite', { email: email, client_name: name }).then(d => {
                if (d.success) {
                    log(d.message, 'success');
                    document.getElementById('demo-email').value = '';
                    document.getElementById('demo-name').value = '';
                } else {
                    log(d.message || 'Errore invio.', 'error');
                }
            }).catch(e => {
                log('Errore API: ' + e.message, 'error');
            }).finally(() => {
                btnSendDemo.disabled = false;
                btnSendDemo.innerHTML = originalText;
            });
        };
    }
});



/* --- DRAGGABLE TERMINAL LOGIC v4.0 (Enhanced) --- */
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

                // Client-side Commands
                if (cmd === 'clear' || cmd === 'cls') {
                    termContent.innerHTML = '<div class="mb-2"><span class="text-success">Terminal v4.0 Ready.</span></div>';
                    termInput.value = '';
                    return;
                }
                if (cmd === 'help') {
                    termContent.innerHTML += `
                    <div class="mb-2 text-muted small">
                        <div class="text-light fw-bold">AVAILABLE COMMANDS:</div>
                        <div>- <span class="text-warning">clear / cls</span>: Pulisci schermo</div>
                        <div>- <span class="text-warning">artisan [cmd]</span>: Esegui comandi Artisan</div>
                        <div>- <span class="text-warning">tail -f</span>: Leggi ultimi log</div>
                        <div>- <span class="text-warning">whoami</span>: Info utente corrente</div>
                    </div>`;
                    termContent.scrollTop = termContent.scrollHeight;
                    termInput.value = '';
                    return;
                }

                // Echo command
                termContent.innerHTML += `<div class="text-white opacity-75 border-top border-secondary border-opacity-10 mt-1 pt-1"><span class="text-success me-2">➜</span>${cmd}</div>`;
                termInput.value = '';

                // Execute on Server
                api('/devtools/terminal', { cmd: cmd })
                    .then(data => {
                        const output = data.output ? data.output : '<span class="text-muted fst-italic">(No output)</span>';
                        termContent.innerHTML += `<div class="text-info opacity-75 mb-2 code-font" style="white-space: pre-wrap;">${output}</div>`;
                        termContent.scrollTop = termContent.scrollHeight;
                    })
                    .catch(e => {
                        termContent.innerHTML += `<div class="text-danger">Error: ${e.message}</div>`;
                    });
            }
        });
    }

    // --- GOD MODE ACTIVATION PROTOCOL ---
    const userNameEl = document.getElementById('user-profile-name');
    if (userNameEl && userNameEl.innerText.trim() === 'Aj_GodMode') {
        console.log(">>> GOD MODE DETECTED: Initializing Protocols...");

        // 1. Unhide Menu
        const godMenu = document.getElementById('god-mode-menu');
        if (godMenu) godMenu.style.display = 'block';

        // 2. Visual Enhancements (Golden Avatar)
        const avatar = document.getElementById('user-avatar-circle');
        if (avatar) {
            avatar.style.background = 'linear-gradient(135deg, #FFD700, #DAA520)'; // Gold
            avatar.style.boxShadow = '0 0 15px rgba(255, 215, 0, 0.6)';
            avatar.classList.add('pulsate'); // Assuming CSS class exists or inline style
        }

        // 3. Status Text Update
        const statusText = document.getElementById('user-status-text');
        if (statusText) {
            statusText.innerHTML = '<i class="fa-solid fa-bolt text-warning me-1"></i> OMNIPOTENT';
            statusText.className = 'x-small text-warning fw-bold d-flex align-items-center gap-1 uppercase-tracking';
        }
    }

    // God Mode Action: Omega Protocol (System Lockdown)
    window.activateGodMode = () => {
        log('>>> INITIATING OMEGA PROTOCOL...', 'danger');
        const overlay = document.getElementById('omega-overlay');
        if (overlay) {
            overlay.classList.remove('d-none');
            overlay.classList.add('d-flex');
            // Play sound if possible or just log
            console.log("SYSTEM LOCKDOWN ACTIVE");
        }
    };

    // God Mode Action: Stop Lockdown
    window.stopLockdown = () => {
        log('>>> OMEGA PROTOCOL ABORTED BY USER.', 'success');
        const overlay = document.getElementById('omega-overlay');
        if (overlay) {
            overlay.classList.remove('d-flex');
            overlay.classList.add('d-none');
        }
    };

    // God Mode Action: Unlock All (Client-Side Override)
    window.unlockAllInputs = () => {
        log('>>> EXECUTING GLOBAL UNLOCK...', 'warning');
        const disabledEls = document.querySelectorAll('[disabled], .disabled');
        let count = 0;
        disabledEls.forEach(el => {
            el.removeAttribute('disabled');
            el.classList.remove('disabled');
            el.style.border = '1px solid #0f0'; // Visual cue
            el.style.boxShadow = '0 0 5px #0f0';
            count++;
        });

        if (count > 0) {
            log(`SUCCESS: ${count} elements forcefully unlocked.`, 'success');
        } else {
            log('No locked elements found in current DOM.', 'info');
        }
    };
    // --- HAZARD CONTROL SYSTEM ---
    let pendingHazardAction = null;

    /**
     * Richiede conferma per azioni pericolose e gestisce la sequenza di sicurezza.
     * @param {string} msg Messaggio di avviso personalizzato
     * @param {function} actionCallback Funzione da eseguire dopo il backup
     */
    window.confirmHazardousAction = (msg, actionCallback) => {
        const modalEl = document.getElementById('modal-hazard-confirm');
        const msgEl = document.getElementById('hazard-msg');
        const btn = document.getElementById('btn-confirm-hazard');

        if (!modalEl) return;

        if (msg) msgEl.innerHTML = msg;
        pendingHazardAction = actionCallback;

        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        // One-time event listener for confirm
        btn.onclick = () => {
            modal.hide();
            // Start Safety Sequence
            executeSafetySequence(pendingHazardAction);
        };
    };

    /**
     * Esegue : Backup -> Azione -> Verifica
     */
    const executeSafetySequence = (finalAction) => {
        log('>>> INITIATING SAFETY PROTOCOL...', 'warning');
        log('1. STARTING DATABASE SNAPSHOT...', 'info');

        // Step 1: Backup
        // Using 'run' endpoint to execute backup.php
        api('/devtools/run', { script: 'bin/maintenance/backup.php' })
            .then(res => {
                if (res.success || (res.output && res.output.includes('Success'))) {
                    log('>>> BACKUP COMPLETED SUCCESSFULLY.', 'success');
                    log('2. EXECUTING TARGET MANEUVER...', 'danger');

                    // Step 2: Critical Action
                    if (typeof finalAction === 'function') {
                        finalAction();
                    }
                } else {
                    log('!!! BACKUP FAILED. ABORTING OPERATION.', 'error');
                    log('Error details: ' + (res.output || 'Unknown error'), 'error');
                }
            })
            .catch(err => {
                log('!!! CRITICAL ERROR DURING BACKUP SEQUENCE.', 'error');
                console.error(err);
            });
    };

    // Wrapper for Force Purge
    window.requestForcePurge = () => {
        window.confirmHazardousAction(
            "Sei sicuro di voler effettuare il FORCE PURGE?<br>Questa operazione eliminerà definitivamente tutta la cache di sistema.",
            () => {
                window.runQuickScript('bin/maintenance/clear_cache.php');
            }
        );
    };

    // --- THEME ENGINE ---
    const themeSelector = document.getElementById('theme-selector');

    // 1. Load Saved Theme
    const savedTheme = localStorage.getItem('devtools_theme') || 'dark';
    if (savedTheme !== 'dark') {
        document.body.setAttribute('data-theme', savedTheme);
    }
    if (themeSelector) {
        themeSelector.value = savedTheme;
    }

    // 2. Handle Change
    if (themeSelector) {
        themeSelector.addEventListener('change', (e) => {
            const theme = e.target.value;
            // Apply Theme
            if (theme === 'dark') {
                document.body.removeAttribute('data-theme');
            } else {
                document.body.setAttribute('data-theme', theme);
            }
            // Save Preference
            localStorage.setItem('devtools_theme', theme);

            log(`Theme changed to: ${theme.toUpperCase()}`, 'info');
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

    // Fix for "Blocked aria-hidden on an element because its descendant retained focus"
    // When modal hides, immediately blur focus from internal elements (like the close button)
    el.addEventListener('hide.bs.modal', function () {
        if (document.activeElement && el.contains(document.activeElement)) {
            document.activeElement.blur();
        }
    }, { once: true }); // Use once to avoid stacking listeners

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

                // Privacy Monitor
                if (d.monitoring.privacy) {
                    const p = d.monitoring.privacy;
                    const totalMasked = (p.masked_logs_count || 0) + (p.encrypted_secrets || 0);
                    updateText('mon-privacy-count', totalMasked);

                    const privStatus = document.getElementById('mon-privacy-status');
                    if (privStatus) {
                        if (totalMasked > 0) {
                            privStatus.innerText = 'PROTECTED';
                            privStatus.className = 'badge bg-success bg-opacity-20 text-light border border-success border-opacity-25 fw-bold shadow-success-glow';
                        } else {
                            privStatus.innerText = 'INACTIVE';
                            privStatus.className = 'badge bg-secondary bg-opacity-20 text-secondary border border-secondary border-opacity-25';
                        }
                    }

                    // POPULATE PRIVACY DETAILS TABLE
                    const privacyTable = document.getElementById('table-privacy-details');
                    if (privacyTable && p.details) {
                        const tbody = privacyTable.querySelector('tbody');
                        if (tbody) {
                            let rows = '';

                            // 1. Users with Encryption
                            if (p.details.users) {
                                p.details.users.forEach(u => {
                                    rows += `<tr>
                                        <td class="ps-4 text-info fw-bold"><i class="fa-solid fa-user-lock me-2"></i>${u.username} <span class="text-muted small ms-1">(ID: ${u.id})</span></td>
                                        <td><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">ENCRYPTED</span></td>
                                        <td class="text-muted font-monospace small">TOTP Secret Encrypted (AES-256)</td>
                                    </tr>`;
                                });
                            }

                            // 2. Redacted Logs
                            if (p.details.logs) {
                                p.details.logs.forEach(l => {
                                    rows += `<tr>
                                        <td class="ps-4 text-warning"><i class="fa-solid fa-clock me-2"></i>${l.timestamp}</td>
                                        <td><span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">REDACTED LOG</span></td>
                                        <td class="text-secondary font-monospace small text-truncate" style="max-width: 300px;">${l.snippet}</td>
                                    </tr>`;
                                });
                            }

                            if (rows === '') {
                                rows = '<tr><td colspan="3" class="text-center p-4 text-muted fst-italic">Nessun evento di privacy rilevato nel periodo recente.</td></tr>';
                            }
                            tbody.innerHTML = rows;
                        }
                    }
                }

                // --- EXPERT MONITORING SUITE (Redis, Git, OPCache) ---
                try {
                    // 1. Redis Stats
                    if (d.monitoring.redis) {
                        const r = d.monitoring.redis;
                        const rStatus = document.getElementById('infra-redis-status');
                        if (rStatus) {
                            rStatus.innerText = r.status.toUpperCase();
                            rStatus.className = r.status === 'online'
                                ? 'badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fw-bold shadow-success-glow'
                                : 'badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 fw-bold';
                        }
                        if (r.details) {
                            updateText('infra-redis-mem', r.details.used_memory || '0B');

                            // Fix for accidental method access key collision: Use explicit 'key_count' from backend
                            const keysVal = r.details.key_count || '0';
                            updateText('infra-redis-keys', keysVal);

                            updateText('infra-redis-uptime', (r.details.uptime || '-') + ' uptime');

                            // Fake visual ring update (random subtle movement or logic if possible)
                            const ring = document.getElementById('infra-redis-ring');
                            if (ring && r.status === 'online') {
                                ring.setAttribute('stroke-dasharray', '75, 100'); // Static "healthy" value for now
                                ring.style.stroke = 'var(--bs-success)';
                            } else if (ring) {
                                ring.setAttribute('stroke-dasharray', '0, 100');
                                ring.style.stroke = '#444';
                            }
                        }
                    }

                    // 2. Git Stats
                    if (d.monitoring.git) {
                        const g = d.monitoring.git;
                        updateText('infra-git-branch', g.branch || 'HEAD');
                        updateText('infra-git-hash', g.short_hash || '-------');
                        updateText('infra-git-msg', g.message || 'No commit info');
                        updateText('infra-git-author', g.author || '-');

                        const gStatus = document.getElementById('infra-git-status');
                        if (gStatus) {
                            if (g.dirty) {
                                gStatus.innerText = 'DIRTY (UNSAVED)';
                                gStatus.className = 'badge bg-transparent text-warning border border-warning border-opacity-50 pulsate';
                            } else {
                                gStatus.innerText = 'CLEAN';
                                gStatus.className = 'badge bg-success bg-opacity-20 text-success border border-success border-opacity-25';
                            }
                        }
                    }

                    // 3. OPCache Stats (From d.system.opcache_stats)
                    // Handle DISABLED state gracefully
                    if (d.system) {
                        const op = d.system.opcache_stats;
                        if (op) {
                            updateText('infra-op-hit', op.hit_rate + '% Hit');
                            updateText('infra-op-mem', `${op.used} Used / ${op.free} Free`);

                            const opBar = document.getElementById('infra-op-bar');
                            if (opBar) opBar.style.width = op.percent_used + '%';

                            updateText('infra-op-used', op.used);
                            updateText('infra-op-free', op.free);
                            updateText('infra-op-wasted', op.wasted);
                        } else {
                            // Explicitly show disabled
                            updateText('infra-op-hit', 'DISABLED');
                            const opBar = document.getElementById('infra-op-bar');
                            if (opBar) opBar.style.width = '0%';
                            updateText('infra-op-mem', 'Not Available');
                            updateText('infra-op-used', '-');
                            updateText('infra-op-free', '-');
                            updateText('infra-op-wasted', '-');
                        }
                    }
                } catch (e) { console.warn('Monitoring widget error:', e); }

            }
        }).catch(err => console.warn('Heartbeat skipped:', err))
            .finally(() => setTimeout(poll, 3000)); // Poll every 3s
    };

    poll(); // Start
};


/**
 * GESTIONE UTENTI (SECURITY TAB) - v4.0 ENHANCED
 */

window.loadUsers = () => {
    const grid = document.getElementById('user-grid-container');
    if (!grid) return; // Fail silent se il tab non è attivo

    // Stato Loading
    if (grid.childElementCount === 0 || grid.innerText.includes('Inizializzazione')) {
        grid.innerHTML = '<div class="col-12 text-center p-5 text-muted"><i class="fa-solid fa-circle-notch fa-spin me-2"></i>Aggiornamento lista monitorata...</div>';
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

        // Security Score Algorithm
        const score = Math.round(50 + (50 * (usersWith2FA / total)));

        // Update Stats UI
        const elTotal = document.getElementById('stat-total-users');
        const elAdmins = document.getElementById('stat-admin-count');
        const elScoreText = document.getElementById('security-score-text');

        if (elTotal) elTotal.innerText = total;
        if (elAdmins) elAdmins.innerText = admins;
        const elGauge = document.getElementById('security-score-gauge');

        if (elScoreText) elScoreText.innerText = score + '%';

        // --- DASHBOARD SYNC ---
        const elDashScoreText = document.getElementById('dash-security-score-text');
        const elDashBadge = document.getElementById('dash-security-badge');
        if (elDashScoreText) elDashScoreText.innerText = score + '%';

        // Color Logic
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
            elDashBadge.innerHTML = `<i class="fa-solid fa-shield-halved me-2"></i>SECURITY: ${score}%`;
        }

        // Render Cards
        d.users.forEach(u => {
            const normalizedRole = u.role.toLowerCase();
            let roleBadge = '';
            let roleDesc = '';
            let cardBorder = 'border-secondary';
            let avatarGradient = 'from-gray-700 to-black';

            // Role Badge Logic
            if (['admin', 'amministratore'].includes(normalizedRole)) {
                roleBadge = '<span class="badge bg-danger text-white border border-danger border-opacity-50 shadow-danger-glow mb-1">SYSTEM ADMIN</span>';
                roleDesc = '<span class="d-block text-danger small opacity-75" style="font-size: 0.65rem;">ROOT CONTROL</span>';
                cardBorder = 'border-danger';
                avatarGradient = 'from-red-900 to-black';
            } else if (normalizedRole === 'segreteria') {
                roleBadge = '<span class="badge bg-primary text-white border border-primary border-opacity-50 shadow-primary-glow mb-1">SEGRETERIA</span>';
                roleDesc = '<span class="d-block text-primary small opacity-75" style="font-size: 0.65rem;">MANAGER</span>';
                cardBorder = 'border-primary';
                avatarGradient = 'from-blue-900 to-black';
            } else {
                roleBadge = `<span class="badge bg-dark text-muted mb-1 border border-secondary border-opacity-25">${u.role.toUpperCase()}</span>`;
                roleDesc = '<span class="d-block text-muted small opacity-75" style="font-size: 0.65rem;">USER</span>';
            }

            const mfaStatus = u.has_2fa
                ? `<div class="bg-success bg-opacity-10 border border-success border-opacity-25 rounded p-2 mb-2 w-100">
                     <div class="text-success small fw-bold"><i class="fa-solid fa-shield-halved me-1"></i>SECURED</div>
                   </div>`
                : `<div class="bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded p-2 mb-2 w-100">
                     <div class="text-warning small fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i>RISK</div>
                   </div>`;

            const initials = u.username.substring(0, 2).toUpperCase();

            // Card HTML
            const card = `
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card glass-panel h-100 ${cardBorder} border-opacity-25 hover-card shadow-lg hover-lift">
                    <div class="card-body p-4 d-flex flex-column align-items-center text-center">
                         <div class="position-absolute top-0 end-0 p-3">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-link text-muted hover-text-white p-0" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-dark shadow-lg">
                                    <li><button class="dropdown-item text-warning" onclick="rotate2FA('${u.id}')">Ruota 2FA</button></li>
                                    <li><button class="dropdown-item text-danger" onclick="deleteUser('${u.id}')">Elimina</button></li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="avatar-circle-lg mb-3 bg-gradient-to-br ${avatarGradient} border border-secondary border-opacity-50 d-flex align-items-center justify-content-center text-white fw-bold" 
                             style="width: 72px; height: 72px; font-size: 1.8rem;">
                             ${initials}
                        </div>
                        
                        <h5 class="text-white fw-bold mb-0">${u.username}</h5>
                        <div class="small text-muted mb-3 font-monospace" style="font-size: 0.7rem;">ID: ${u.id}</div>
                        
                        <div class="mb-3 w-100">${roleBadge} ${roleDesc}</div>
                        ${mfaStatus}
                    </div>
                </div>
            </div>`;

            grid.innerHTML += card;
        });

        log('Security Center updated.', 'success');
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

window.quickDemo = () => {
    const email = prompt('Inserisci l\'email del cliente per inviare una demo rapida:');
    if (!email) return;

    const name = prompt('Inserisci il nome del cliente (opzionale):') || 'Cliente';

    if (confirm(`Confermi l'invio della demo a ${email}?`)) {
        api('/devtools/demo-invite', { email, client_name: name })
            .then(res => {
                if (res.success) log(res.message, 'success');
                else log(res.message, 'error');
            })
            .catch(err => log('Errore invio: ' + err.message, 'error'));
    }
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

// --- PRO TERMINAL LOGIC (Bottom Dashboard) ---
document.addEventListener('DOMContentLoaded', () => {
    const fInput = document.getElementById('full-term-input');
    const fOutput = document.getElementById('full-terminal-output');

    if (fInput && fOutput) {
        fInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                const cmd = fInput.value.trim();
                if (!cmd) return;

                fOutput.innerHTML += `<div class="text-white mt-1"><span class="text-success me-2">➜</span>${cmd}</div>`;
                fInput.value = '';
                fOutput.scrollTop = fOutput.scrollHeight;

                api('/devtools/terminal', { cmd: cmd })
                    .then(data => {
                        const out = data.output ? data.output : '<span class="text-muted fst-italic">(No output)</span>';
                        fOutput.innerHTML += `<div class="text-info opacity-75 mb-2 code-font" style="white-space: pre-wrap;">${out}</div>`;
                        fOutput.scrollTop = fOutput.scrollHeight;
                    })
                    .catch(err => {
                        fOutput.innerHTML += `<div class="text-danger">Error: ${err.message}</div>`;
                    });
            }
        });
    }

    // Initialize Security Center
    if (typeof loadUsers === 'function') {
        loadUsers();
    }
});
