const outputEl = document.getElementById('output');
const cmdInput = document.getElementById('cmdInput');
const countEl = document.getElementById('live-count');

// --- CORTEX OS ENGINE (NEURAL INTERFACE) ---
class CortexEngine {
    constructor() {
        this.canvas = document.getElementById('neural-canvas');
        this.ctx = this.canvas.getContext('2d');
        this.nodes = [];
        this.active = false;
        this.resize();
        window.addEventListener('resize', () => this.resize());
    }

    resize() {
        this.canvas.width = window.innerWidth;
        this.canvas.height = window.innerHeight;
    }

    ignite() {
        this.active = true;
        this.canvas.style.opacity = 1;
        // Generate Nodes
        this.nodes = [];
        const nodeCount = 80;
        for (let i = 0; i < nodeCount; i++) {
            this.nodes.push({
                x: Math.random() * this.canvas.width,
                y: Math.random() * this.canvas.height,
                vx: (Math.random() - 0.5) * 1.5,
                vy: (Math.random() - 0.5) * 1.5,
                size: Math.random() * 3 + 1
            });
        }
        this.loop();
    }

    shutdown() {
        this.active = false;
        this.canvas.style.opacity = 0;
    }

    loop() {
        if (!this.active) return;
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        // Draw Connections
        this.ctx.strokeStyle = 'rgba(120, 81, 169, 0.15)'; // Neural Purple
        this.ctx.lineWidth = 1;

        for (let i = 0; i < this.nodes.length; i++) {
            const nodeA = this.nodes[i];

            // Move
            nodeA.x += nodeA.vx;
            nodeA.y += nodeA.vy;

            // Bounce
            if (nodeA.x < 0 || nodeA.x > this.canvas.width) nodeA.vx *= -1;
            if (nodeA.y < 0 || nodeA.y > this.canvas.height) nodeA.vy *= -1;

            // Draw Node
            this.ctx.beginPath();
            this.ctx.arc(nodeA.x, nodeA.y, nodeA.size, 0, Math.PI * 2);
            this.ctx.fillStyle = '#FFD700'; // Gold Synapse
            this.ctx.fill();

            // Connect
            for (let j = i + 1; j < this.nodes.length; j++) {
                const nodeB = this.nodes[j];
                const dx = nodeA.x - nodeB.x;
                const dy = nodeA.y - nodeB.y;
                const dist = Math.sqrt(dx * dx + dy * dy);

                if (dist < 150) {
                    this.ctx.beginPath();
                    this.ctx.moveTo(nodeA.x, nodeA.y);
                    this.ctx.lineTo(nodeB.x, nodeB.y);
                    this.ctx.stroke();
                }
            }
        }

        requestAnimationFrame(() => this.loop());
    }
}

const cortex = new CortexEngine();
let mode = localStorage.getItem('mcag_ux_mode') || 'hyper';

function toggleMode() {
    if (mode === 'hyper') {
        mode = 'neural';
        document.body.classList.add('neural-mode');
        cortex.ignite();
        log('>>> CORTEX OS INTEGRATION: ACTIVE. SYNAPTIC LINK ESTABLISHED.', 'success');
    } else {
        mode = 'hyper';
        document.body.classList.remove('neural-mode');
        cortex.shutdown();
        log('>>> HYPER-GRID RESTORED. LOGIC SYSTEMS ONLINE.', 'info');
    }
    localStorage.setItem('mcag_ux_mode', mode);
}

// Init Mode
if (mode === 'neural') {
    document.body.classList.add('neural-mode');
    setTimeout(() => cortex.ignite(), 100);
}

function log(text, type = 'info') {
    const timestamp = new Date().toLocaleTimeString();
    let prefix = `[${timestamp}] `;
    if (type === 'error') prefix += '[ERR] ';
    if (type === 'success') prefix += '[OK] ';

    outputEl.innerText += '\n' + prefix + text;
    document.getElementById('terminal').scrollTop = document.getElementById('terminal').scrollHeight;
}

async function apiCall(action, data = {}) {
    try {
        let url = `?action=${action}`;
        if (action === 'run_test') url += `&file=${data.file}`;

        const opts = { method: 'POST', body: JSON.stringify(data) };

        // DATA-DRIVEN ACTIONS NEED POST
        const postActions = ['run_cmd', 'ai_chat', 'nmap', 'whois', 'dns', 'fs_op'];
        const usePost = postActions.includes(action);

        const res = await fetch(url, usePost ? opts : undefined);
        const json = await res.json();

        if (json.data) {
            if (action === 'refresh_stats') {
                countEl.innerText = json.data.total;
                // Only log if changed? Nah excessive logging is cool.
                // Actually, let's NOT log stats refresh to keep terminal clean.
                return;
            }
            if (action === 'fs_op' && data.op === 'read') return json; // Special case for read (handled by caller)
            if (action === 'fs_op' && data.op === 'list') return json;

            log(JSON.stringify(json.data, null, 2), 'success');
        } else if (json.output) {
            log(json.output);
        }
        return json; // Return for caller use
    } catch (e) {
        log(e.message, 'error');
        return {};
    }
}

// Live Count Update (Poll every 5s)
setInterval(() => {
    apiCall('refresh_stats');
}, 5000);

function runTest(file) {
    log(`INITIATING TEST MODULE: ${file}...`);
    apiCall('run_test', { file });
}

function fetchGitStatus() {
    log('QUERYING GIT REPOSITORY...');
    apiCall('git_status');
}

function fetchLogs() {
    log('INTERCEPTING SYSTEM LOGS...');
    apiCall('read_logs');
}

// --- HYBRID ENGINE: REAL TOOLS + SIMULATION ---
function runSim(tool, title) {
    // LIST OF REAL TOOLS
    const realTools = ['nmap', 'whois', 'dns'];

    if (realTools.includes(tool)) {
        const target = prompt(`ENTER TARGET FOR ${title}:`, 'localhost');
        if (!target) return;

        log(`>>> EXECUTING REAL ${title} TARGETING [${target}]...`, 'info');
        apiCall(tool, { target: target });
        return;
    }

    // FALLBACK TO SIMULATION FOR OTHERS
    log(`>>> INITIALIZING ${title} (SIMULATION MODE)...`, 'info');

    const steps = [
        "Loading modules...",
        "Connecting to local interface...",
        "Bypassing simulated firewalls...",
        "Running heuristic analysis...",
        "Decrypting data streams...",
        "Compiling report..."
    ];

    let i = 0;
    const interval = setInterval(() => {
        if (i >= steps.length) {
            clearInterval(interval);
            log(`>>> ${title} COMPLETE. REPORT SAVED TO /VAR/LOGS/SECURE.`, 'success');
        } else {
            // Random hex output for effect
            const hex = Math.random().toString(16).substr(2, 8).toUpperCase();
            log(`[${hex}] ${steps[i]}`);
            i++;
        }
    }, 600);
}

function purgeCache() {
    if (confirm('WARNING: THIS WILL NUKE SYSTEM CACHE. PROCEED?')) {
        log('INITIATING CACHE PURGE PROTOCOL...');
        apiCall('purge_cache');
    }
}

// --- UNIVERSAL SHELL & OMNI-EDITOR LOGIC ---
let shellMode = 'cmd'; // cmd, ps, py, ai
let editorVisible = false;

function toggleShell(mode) {
    shellMode = mode;
    const promptSpan = document.querySelector('.terminal-input-line span');
    const input = document.getElementById('cmdInput');

    // Reset Styles
    document.querySelectorAll('.shell-toggle').forEach(btn => btn.style.background = 'transparent');

    if (mode === 'cmd') {
        promptSpan.innerText = 'admin@hypergrid:~$';
        promptSpan.style.color = 'var(--neon-green)';
        input.placeholder = "CMD (Legacy)...";
        log(">>> SHELL: COMMAND PROMPT (LEGACY) ACTIVE.", 'info');
    } else if (mode === 'ps') {
        promptSpan.innerText = 'PS C:\\HyperGrid>';
        promptSpan.style.color = '#00f3ff';
        input.placeholder = "PowerShell Cmdlet...";
        log(">>> SHELL: POWERSHELL CORE ACTIVE.", 'info');
    } else if (mode === 'py') {
        promptSpan.innerText = '>>>';
        promptSpan.style.color = '#ffd700'; // Yellow
        input.placeholder = "Python Statement...";
        log(">>> SHELL: PYTHON INTERACTIVE ACTIVE.", 'info');
    } else if (mode === 'ai') {
        promptSpan.innerText = 'ai@cortex:~$';
        promptSpan.style.color = 'var(--neon-pink)';
        input.placeholder = "Ask AI...";
        log(">>> SHELL: CORTEX AI ENGAGED.", 'success');
    }
}

// Editor Toggle
function toggleEditor() {
    editorVisible = !editorVisible;
    document.getElementById('editor-modal').style.display = editorVisible ? 'flex' : 'none';
    if (editorVisible) loadDir('');
}

// File System Ops
async function loadDir(path) {
    const res = await apiCall('fs_op', { op: 'list', path: path });
    const json = res; // apiCall now returns json
    const listEl = document.getElementById('file-list');
    listEl.innerHTML = '';

    if (path !== '') {
        // Up Button
        const up = path.split('/').slice(0, -1).join('/');
        listEl.innerHTML += `<div onclick="loadDir('${up}')" style="cursor:pointer; color:#888;">.. [UP]</div>`;
    }

    if (json.data) {
        json.data.forEach(f => {
            const color = f.type === 'dir' ? '#00f3ff' : '#ccc';
            const icon = f.type === 'dir' ? 'fa-folder' : 'fa-file';
            const click = f.type === 'dir' ? `loadDir('${path ? path + '/' + f.name : f.name}')` : `loadFile('${path ? path + '/' + f.name : f.name}')`;
            listEl.innerHTML += `<div onclick="${click}" style="cursor:pointer; color:${color}; padding:2px;">
                        <i class="fa-solid ${icon}"></i> ${f.name}
                    </div>`;
        });
    }
}

async function loadFile(path) {
    document.getElementById('editor-filename').value = path;
    const json = await apiCall('fs_op', { op: 'read', path: path });
    document.getElementById('code-area').value = json.data || '';
}

async function saveFile() {
    const path = document.getElementById('editor-filename').value;
    const content = document.getElementById('code-area').value;
    apiCall('fs_op', { op: 'write', path: path, content: content });
}

function createNewFile(ext) {
    document.getElementById('code-area').value = '';
    const newName = `new_file_${Date.now()}.${ext}`;
    const root = document.getElementById('editor-filename').value.substring(0, document.getElementById('editor-filename').value.lastIndexOf('/')) || '';

    // Set default path, user can edit
    document.getElementById('editor-filename').value = root ? `${root}/${newName}` : newName;
    document.getElementById('editor-filename').focus();
    log(`>>> NEW ${ext.toUpperCase()} TEMPLATE CREATED.`, 'info');
}

function runScript() {
    const path = document.getElementById('editor-filename').value;
    if (path.endsWith('.php')) {
        apiCall('run_cmd', { cmd: `php "${path}"`, mode: 'cmd' });
    } else if (path.endsWith('.py')) {
        apiCall('run_cmd', { cmd: `python "${path}"`, mode: 'cmd' }); // cmd mode runs python binary
    } else if (path.endsWith('.java')) {
        // Compile and Run Single File
        // javac File.java && java File
        // Assuming class name matches file name
        const className = path.split('/').pop().replace('.java', '');
        // For simplicity, just run java source file (Java 11+)
        apiCall('run_cmd', { cmd: `java "${path}"`, mode: 'cmd' });
    } else {
        log("CANNOT RUN THIS FILE TYPE DIRECTLY. USE TERMINAL.", 'error');
    }
    toggleEditor(); // Close to see output
}

// --- AI MODE LOGIC WRAPPER (Modified for Shell Switcher) ---
function toggleAiMode() {
    toggleShell(shellMode === 'ai' ? 'cmd' : 'ai');
}

// CMD Input
cmdInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        const cmd = cmdInput.value;
        if (!cmd) return;

        log(`> ${cmd}`);
        cmdInput.value = '';

        // SHELL ROUTER
        if (shellMode === 'ai') {
            apiCall('ai_chat', { prompt: cmd });
        } else {
            // Send MODE (cmd, ps, py) to backend
            apiCall('run_cmd', { cmd: cmd, mode: shellMode });
        }
    }
});

// --- DRAGGABLE MODAL LOGIC ---
setTimeout(() => {
    const modal = document.getElementById('editor-modal');
    const header = document.getElementById('editor-header');

    let isDragging = false;
    let currentX;
    let currentY;
    let initialX;
    let initialY;
    let xOffset = 0;
    let yOffset = 0;

    if (header) {
        header.addEventListener("mousedown", dragStart);
        document.addEventListener("mouseup", dragEnd);
        document.addEventListener("mousemove", drag);
        console.log("Draggable logic attached to header");
    } else {
        console.error("Editor Header not found for draggable logic");
    }

    function dragStart(e) {
        initialX = e.clientX - xOffset;
        initialY = e.clientY - yOffset;

        if (e.target === header || e.target.parentNode === header) {
            isDragging = true;
        }
    }

    function dragEnd(e) {
        initialX = currentX;
        initialY = currentY;
        isDragging = false;
    }

    function drag(e) {
        if (isDragging) {
            e.preventDefault();
            currentX = e.clientX - initialX;
            currentY = e.clientY - initialY;

            xOffset = currentX;
            yOffset = currentY;

            setTranslate(currentX, currentY, modal);
        }
    }

    function setTranslate(xPos, yPos, el) {
        el.style.transform = `translate(calc(-50% + ${xPos}px), calc(-50% + ${yPos}px))`;
    }
}, 500); // Delay slightly to ensure DOM is ready

