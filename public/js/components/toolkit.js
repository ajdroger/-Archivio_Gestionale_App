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
    verbose: true,        // Default to verbose for details
    stopOnFailure: false,
    autoClear: false      // Keep history by default
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
// --- CONSOLE DRAG PHYSICS (RESIZABLE) ---
function initDraggableConsole() {
    const drawer = document.getElementById('console-drawer');
    const handle = document.getElementById('console-handle');
    if (!drawer || !handle) return;

    let isDragging = false;

    const start = (e) => {
        isDragging = true;
        drawer.style.transition = 'none'; // Disable transition for instant resize
        e.preventDefault(); // Prevent text selection
    };

    const move = (e) => {
        if (!isDragging) return;

        const clientY = e.clientY || e.touches[0].clientY;
        const newHeight = window.innerHeight - clientY;

        // Apply Constraints (Min 32px, Max Window Height)
        if (newHeight < 32) return;
        if (newHeight > window.innerHeight) return;

        drawer.style.height = `${newHeight}px`;
        drawer.style.transform = 'translateY(0)'; // Ensure it stays visible

        // Auto-mark open if dragged up
        if (newHeight > 60) {
            drawer.classList.add('open');
            document.querySelector('.cmd-input').focus();
        } else {
            drawer.classList.remove('open');
        }
    };

    const end = () => {
        if (!isDragging) return;
        isDragging = false;
        drawer.style.transition = ''; // Restore CSS transition

        // Snap logic if desired (Optional - keeping "Freedom" requested by user)
        // If < 100px, maybe close it?
        const currentHeight = parseInt(drawer.style.height);
        if (currentHeight < 100) {
            drawer.style.height = ''; // Reset to CSS default
            drawer.classList.remove('open');
        }
    };

    handle.addEventListener('mousedown', start);
    document.addEventListener('mousemove', move);
    document.addEventListener('mouseup', end);

    handle.addEventListener('touchstart', start, { passive: false });
    document.addEventListener('touchmove', move, { passive: false });
    document.addEventListener('touchend', end);
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
// --- RUNNER FUNCTIONS ---
window.runTest = function (relPath) {
    // 1. Prepare Console
    const drawer = document.getElementById('console-drawer');
    if (!drawer.classList.contains('open')) {
        drawer.classList.add('open');
    }

    // Auto-clear if config set
    if (config.autoClear) {
        document.getElementById('console-content').innerHTML = '';
        appendConsoleOutput(`<span style="color:var(--ent-text-muted)">// Console cleared automatically</span>`);
    }

    appendConsoleOutput(`Executing: <span style="color:var(--ent-accent-amber)">${relPath}</span>...`);
    appendConsoleOutput(`<span class="text-muted">...running...</span>`);

    let url = `test_dashboard.php?action=run_test&file=${encodeURIComponent(relPath)}`;
    if (config.verbose) url += '&verbose=true';

    fetch(url)
        .then(r => r.text())
        .then(text => {
            // Remove "...running" placeholder if possible (or just append)

            // Append formatted output
            appendConsoleOutput(ansiToHtml(text));

            // Summary Line
            if (text.includes('FAIL') || text.includes('Error')) {
                appendConsoleOutput(`\n<span style="color:#ef4444; font-weight:bold;">[EXIT STATUS] FAILED</span>`, 'error');
            } else {
                appendConsoleOutput(`\n<span style="color:#10b981; font-weight:bold;">[EXIT STATUS] PASSED</span>`);
            }
        })
        .catch(e => {
            appendConsoleOutput(`CRITICAL ERROR:\n${e}`, 'error');
        });
}

window.runAll = function () {
    if (!confirm("Execute FULL SUITE? This is intensive.")) return;
    runTest('bin/debug_tools/safe_test_runner.php');
}

window.clearLog = function () {
    document.getElementById('console-content').innerHTML = '';
    appendConsoleOutput("// Console cleared.");
}

window.toggleConsole = function () {
    const drawer = document.getElementById('console-drawer');
    drawer.classList.toggle('open');
    // Also reset maximize if closing
    if (!drawer.classList.contains('open')) {
        drawer.classList.remove('maximized');
        const icon = document.getElementById('icon-max');
        if (icon) icon.className = 'fa-solid fa-expand';
    }
}

window.toggleMaximize = function () {
    const drawer = document.getElementById('console-drawer');
    drawer.classList.toggle('maximized');

    // Update Icon
    const icon = document.getElementById('icon-max');
    if (icon) {
        if (drawer.classList.contains('maximized')) {
            icon.className = 'fa-solid fa-compress';
        } else {
            icon.className = 'fa-solid fa-expand';
        }
    }

    // Ensure it is marked as open
    if (!drawer.classList.contains('open')) {
        drawer.classList.add('open');
    }
}

// Helper: ANSI to HTML (Basic)
function ansiToHtml(text) {
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/\n/g, '<br>')
        .replace(/\[32m/g, '<span style="color:#10b981">') // Green
        .replace(/\[31m/g, '<span style="color:#ef4444">') // Red
        .replace(/\[33m/g, '<span style="color:#f59e0b">') // Yellow
        .replace(/\[36m/g, '<span style="color:#3b82f6">') // Blue/Cyan
        .replace(/\[0m/g, '</span>')
        .replace(/\[1m/g, '<span style="font-weight:bold">');
}

