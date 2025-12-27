// --- CONSOLE & DRAGGING ---
document.addEventListener('DOMContentLoaded', () => {
    // FORCE MODAL CLOSED (Fix for auto-open issue)
    const settingsModal = document.getElementById('modal-settings');
    if (settingsModal) {
        settingsModal.classList.remove('show');
        settingsModal.style.display = 'none';
        settingsModal.setAttribute('aria-hidden', 'true');
        // Remove backdrop if exists
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) backdrop.remove();
    }

    const drawer = document.getElementById('terminal-drawer');
    const handle = document.getElementById('terminal-drag-handle');
    /* Robust Drag Logic */
    let isDragging = false;
    let offset = { x: 0, y: 0 };

    if (handle) {
        handle.addEventListener('mousedown', (e) => {
            // Ignore button clicks inside handle
            if (e.target.closest('button')) return;
            isDragging = true;
            offset.x = e.clientX - drawer.offsetLeft;
            offset.y = e.clientY - drawer.offsetTop;
            handle.style.cursor = 'grabbing';
        });
    }

    document.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        e.preventDefault();
        drawer.style.left = (e.clientX - offset.x) + 'px';
        drawer.style.top = (e.clientY - offset.y) + 'px';
    });

    document.addEventListener('mouseup', () => {
        isDragging = false;
        if (handle) handle.style.cursor = 'grab';
    });

    // --- INTERACTIVE SHELL LOGIC ---
    const termInput = document.getElementById('term-input');
    const termContent = document.getElementById('terminal-content');

    if (termInput) {
        termInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                const cmd = termInput.value.trim();
                if (!cmd) return;

                // Echo command
                termContent.innerHTML += `<div class="text-white opacity-75 border-top border-secondary border-opacity-10 mt-1 pt-1"><span class="text-success me-2">PS ></span>${cmd}</div>`;
                termContent.scrollTop = termContent.scrollHeight;
                termInput.value = '';

                // Execute
                fetch('terminal.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cmd: cmd })
                })
                    .then(r => r.json())
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

    // Load Settings from LocalStorage
    const config = {
        verbose: localStorage.getItem('tk_verbose') === 'true',
        stopOnFailure: localStorage.getItem('tk_stop_failure') === 'true',
        autoClear: (localStorage.getItem('tk_auto_clear') || 'true') === 'true'
    };

    // Sync UI switches
    const elVerbose = document.getElementById('setting-verbose');
    const elStop = document.getElementById('setting-stop-failure');
    const elAuto = document.getElementById('setting-auto-clear');

    if (elVerbose) elVerbose.checked = config.verbose;
    if (elStop) elStop.checked = config.stopOnFailure;
    if (elAuto) elAuto.checked = config.autoClear;

    // Handle switch changes
    const updateStatusBadge = () => {
        let activeCount = 0;
        if (elVerbose && elVerbose.checked) activeCount++;
        if (elStop && elStop.checked) activeCount++;

        const badge = document.getElementById('header-status-badge');
        if (badge) {
            if (activeCount > 0) {
                badge.innerText = activeCount;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
    };

    if (elVerbose) {
        elVerbose.addEventListener('change', (e) => {
            localStorage.setItem('tk_verbose', e.target.checked);
            updateStatusBadge();
        });
    }
    if (elStop) {
        elStop.addEventListener('change', (e) => {
            localStorage.setItem('tk_stop_failure', e.target.checked);
            updateStatusBadge();
        });
    }
    if (elAuto) {
        elAuto.addEventListener('change', (e) => {
            localStorage.setItem('tk_auto_clear', e.target.checked);
        });
    }

    // Initial badge update
    updateStatusBadge();
});

window.toggleTerminal = function () {
    const d = document.getElementById('terminal-drawer');
    const isHidden = window.getComputedStyle(d).display === 'none';
    d.style.display = isHidden ? 'flex' : 'none';
}

window.clearLog = () => document.getElementById('terminal-content').innerHTML = '';

function log(msg, type = 'info') {
    const term = document.getElementById('terminal-content');
    const time = new Date().toLocaleTimeString('it-IT', { hour12: false });
    let color = '#ccc';
    if (type == 'error') color = '#ef4444';
    if (type == 'success') color = '#22c55e';
    if (type == 'warning') color = '#f59e0b';

    term.innerHTML += `<div style="color:${color}; border-bottom:1px solid #222; padding:2px 0;">
        <span style="opacity:0.5; font-size:0.7em; margin-right:5px;">${time}</span>${msg}
    </div>`;
    term.scrollTop = term.scrollHeight;

    if (type == 'error' || type == 'warning') {
        const d = document.getElementById('terminal-drawer');
        if (window.getComputedStyle(d).display === 'none') d.style.display = 'flex';
    }
}

// --- RUNNER LOGIC ---
function runTest(relPath) {
    const isVerbose = localStorage.getItem('tk_verbose') === 'true';
    const isStopOnFailure = localStorage.getItem('tk_stop_failure') === 'true';
    const isAutoClear = (localStorage.getItem('tk_auto_clear') || 'true') === 'true';

    if (isAutoClear) window.clearLog();
    log(`Starting: ${relPath}${isVerbose ? ' [VERBOSE]' : ''}...`, 'info');

    // Show Modal Loader
    const modal = document.getElementById('outputModal');
    const out = document.getElementById('termOutput');
    modal.style.display = 'block';
    out.innerHTML = '<div class="text-center mt-5"><i class="fa-solid fa-spinner fa-spin fs-1 text-primary"></i><br>Executing...</div>';

    let url = 'run_test.php?file=' + encodeURIComponent(relPath);
    if (isVerbose) url += '&verbose=true';
    if (isStopOnFailure) url += '&stop-on-failure=true';

    fetch(url)
        .then(r => r.text())
        .then(text => {
            out.innerHTML = text; // Show full output in modal

            // Parse for quick console summary
            if (text.includes('FAIL') || text.includes('Error')) {
                log(`${relPath}: FAILED`, 'error');
            } else {
                log(`${relPath}: PASSED`, 'success');
            }
        })
        .catch(e => {
            out.innerHTML = "AJAX Error: " + e;
            log("Network Error", 'error');
        });
}

window.closeModal = () => document.getElementById('outputModal').style.display = 'none';

function runAll() {
    if (!confirm("Eseguire TUTTE le suite con SAFE RUNNER?\nQuesto effettuerà un backup preventivo e verificherà 130+ test.")) return;
    // Trigger Safe Test Runner
    runTest('bin/debug_tools/safe_test_runner.php');
}
