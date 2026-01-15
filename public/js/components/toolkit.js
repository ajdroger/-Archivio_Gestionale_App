// --- MCAG ENTERPRISE TOOLKIT v5.5 ---
// Logic Controller for Test Dashboard

document.addEventListener('DOMContentLoaded', () => {
    // 1. Force Modal Cleanup
    const modals = document.querySelectorAll('.modal-backdrop');
    modals.forEach(m => m.remove());

    // 2. Load Preferences
    loadPreferences();

    // 3. Initialize Console Dragging
    initDraggableConsole();

    // 4. Initialize Console Input
    initConsoleInput();

    logSystem("MCAG Enterprise Toolkit loaded successfully.");
});

// --- PREFERENCES MANAGEMENT ---
const config = {
    verbose: false,
    stopOnFailure: false,
    autoClear: true
};

function loadPreferences() {
    config.verbose = localStorage.getItem('ent_verbose') === 'true';
    config.stopOnFailure = localStorage.getItem('ent_stop_failure') === 'true';
    config.autoClear = (localStorage.getItem('ent_auto_clear') || 'true') === 'true';

    // Sync UI
    const elVerbose = document.getElementById('setting-verbose');
    const elStop = document.getElementById('setting-stop-failure');
    const elAuto = document.getElementById('setting-auto-clear');

    if (elVerbose) {
        elVerbose.checked = config.verbose;
        elVerbose.addEventListener('change', (e) => {
            config.verbose = e.target.checked;
            localStorage.setItem('ent_verbose', config.verbose);
        });
    }
    if (elStop) {
        elStop.checked = config.stopOnFailure;
        elStop.addEventListener('change', (e) => {
            config.stopOnFailure = e.target.checked;
            localStorage.setItem('ent_stop_failure', config.stopOnFailure);
        });
    }
    if (elAuto) {
        elAuto.checked = config.autoClear;
        elAuto.addEventListener('change', (e) => {
            config.autoClear = e.target.checked;
            localStorage.setItem('ent_auto_clear', config.autoClear);
        });
    }
}

// --- CONSOLE DRAG PHYSICS ---
function initDraggableConsole() {
    const drawer = document.getElementById('console-drawer');
    const handle = document.getElementById('console-handle');
    if (!drawer || !handle) return;

    let startY = 0;
    let currentY = 0;
    let isDragging = false;

    // Mobile/Desktop unification
    const start = (y) => {
        isDragging = true;
        startY = y;
        drawer.style.transition = 'none'; // Disable transition for instant follow
    };

    const move = (y) => {
        if (!isDragging) return;
        const delta = startY - y; // Positive = Dragging Up
        if (delta < 0) return; // Don't detach from bottom
        // Logic for "following" finger not strictly needed if we just toggle, 
        // but for "feel" we can slightly translate via transform if desired.
    };

    const end = (y) => {
        if (!isDragging) return;
        isDragging = false;
        drawer.style.transition = 'transform 0.3s cubic-bezier(0.2, 0.8, 0.2, 1)'; // Restore physics
        const delta = startY - y;

        if (delta > 50) { // Dragged Up significantly
            drawer.classList.add('open');
            document.querySelector('.cmd-input').focus();
        } else if (delta < -50) { // Dragged Down significantly
            drawer.classList.remove('open');
            document.querySelector('.cmd-input').blur();
        }
    };

    handle.addEventListener('mousedown', e => start(e.clientY));
    document.addEventListener('mousemove', e => move(e.clientY));
    document.addEventListener('mouseup', e => end(e.clientY));

    handle.addEventListener('touchstart', e => start(e.touches[0].clientY), { passive: true });
    document.addEventListener('touchmove', e => move(e.touches[0].clientY), { passive: true });
    document.addEventListener('touchend', e => end(e.changedTouches[0].clientY));
}

// --- CONSOLE COMMANDS ---
function initConsoleInput() {
    const input = document.querySelector('.cmd-input');
    if (!input) return;

    input.disabled = false; // ENABLE INPUT
    input.focus();

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            const cmd = input.value.trim();
            if (!cmd) return;

            // 1. Echo Command
            appendConsoleOutput(`<span class="text-success">ADMIN@MCAG:~#</span> ${cmd}`);
            input.value = '';

            // 2. Client-Side Quick Commands
            if (cmd === 'cls' || cmd === 'clear') {
                document.getElementById('console-content').innerHTML = '';
                return;
            }

            // 3. Process Backend Command
            processCommand(cmd);
        }
    });
}

function processCommand(cmd) {
    appendConsoleOutput(`<span class="text-muted">...prossessing</span>`);

    // Check if it's a specific 'run' alias
    if (cmd.startsWith('run ')) {
        const target = cmd.substring(4);
        if (target === 'all') {
            runAll();
            return;
        }
        // Assume file path
        runTest(target);
        return;
    }

    // Generic Backend Command
    fetch('test_dashboard.php?action=run_cmd', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cmd: cmd })
    })
        .then(r => r.json())
        .then(data => {
            if (data.output === '__CLEAR__') {
                document.getElementById('console-content').innerHTML = '';
            } else {
                appendConsoleOutput(data.output);
            }
        })
        .catch(e => appendConsoleOutput(`Error: ${e}`, 'error'));
}

function appendConsoleOutput(html, type = 'info') {
    const content = document.getElementById('console-content');
    const div = document.createElement('div');
    div.style.padding = "2px 0";
    div.style.borderBottom = "1px solid rgba(255,255,255,0.05)";
    if (type === 'error') div.style.color = '#ef4444';

    // Remove "...processing" placeholder if exists (last child)
    if (content.lastChild && content.lastChild.textContent.includes('...prossessing')) {
        content.lastChild.remove();
    }

    div.innerHTML = html;
    content.appendChild(div);
    content.scrollTop = content.scrollHeight;
}

function logSystem(msg) {
    appendConsoleOutput(`<span style="color:var(--ent-accent-blue)">[SYSTEM]</span> ${msg}`);
}

// --- RUNNER FUNCTIONS ---
window.runTest = function (relPath) {
    if (config.autoClear) {
        document.getElementById('console-content').innerHTML = '';
        document.getElementById('termOutput').innerHTML = '';
    }

    // Open Console if closed
    const drawer = document.getElementById('console-drawer');
    if (!drawer.classList.contains('open')) {
        drawer.classList.add('open');
    }

    appendConsoleOutput(`Executing: <span style="color:var(--ent-accent-amber)">${relPath}</span>...`);

    // Open Detailed Modal
    const modal = document.getElementById('outputModal');
    modal.style.display = 'block';

    let url = `test_dashboard.php?action=run_test&file=${encodeURIComponent(relPath)}`;
    if (config.verbose) url += '&verbose=true';

    fetch(url)
        .then(r => r.text())
        .then(text => {
            // Put in Modal
            document.getElementById('termOutput').innerHTML = ansiToHtml(text);

            // Console Summary
            if (text.includes('FAIL') || text.includes('Error')) {
                appendConsoleOutput(`Result: <span style="color:#ef4444; font-weight:bold;">FAILED</span>`, 'error');
            } else {
                appendConsoleOutput(`Result: <span style="color:#10b981; font-weight:bold;">PASSED</span>`);
            }
        })
        .catch(e => {
            document.getElementById('termOutput').innerHTML = `CRITICAL ERROR:\n${e}`;
            appendConsoleOutput(`Network Error during execution.`, 'error');
        });
}

window.runAll = function () {
    if (!confirm("Execute FULL SUITE? This is intensive.")) return;
    runTest('bin/debug_tools/safe_test_runner.php'); // Assuming this exists or map to a meta-runner
}

window.clearLog = function () {
    document.getElementById('console-content').innerHTML = '';
}

window.closeModal = function () {
    document.getElementById('outputModal').style.display = 'none';
}

// Helper: ANSI to HTML (Basic)
function ansiToHtml(text) {
    return text
        .replace(/\n/g, '<br>')
        .replace(/\[32m/g, '<span style="color:#10b981">') // Green
        .replace(/\[31m/g, '<span style="color:#ef4444">') // Red
        .replace(/\[33m/g, '<span style="color:#f59e0b">') // Yellow
        .replace(/\[0m/g, '</span>')
        .replace(/\[1m/g, '<span style="font-weight:bold">');
}

