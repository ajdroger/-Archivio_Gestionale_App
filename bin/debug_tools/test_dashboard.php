<?php
/**
 * MCAG HYPER-GRID TOOLKIT v7.1
 * "QUANTUM ENGINEERING DECK"
 * 
 * @version 7.2.0-god-mode
 */

require_once __DIR__ . '/../../vendor/autoload.php';
date_default_timezone_set('Europe/Rome');

// --- BACKEND LOGIC ---

// 1. GIT INFO
function getGitInfo()
{
    $root = __DIR__ . '/../../';
    $branch = trim(shell_exec("cd \"$root\" && git rev-parse --abbrev-ref HEAD"));
    $hash = trim(shell_exec("cd \"$root\" && git rev-parse --short HEAD"));
    $status = trim(shell_exec("cd \"$root\" && git status --porcelain"));
    return ['branch' => $branch, 'hash' => $hash, 'dirty' => !empty($status)];
}

// 2. LOG READER (Tail)
function getLogTail($lines = 50)
{
    $logFile = __DIR__ . '/../../storage/logs/app.log';
    if (!file_exists($logFile))
        return ["Log file not found."];
    $data = file($logFile);
    return array_slice($data, -$lines);
}

// 3. CACHE PURGE
function purgeCache()
{
    $dirs = [__DIR__ . '/../../var/cache', __DIR__ . '/../../tmp'];
    $results = [];
    foreach ($dirs as $dir) {
        if (!is_dir($dir))
            continue;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        $results[] = "Purged: " . basename($dir);
    }
    return $results;
}

// 4. PRECISE TEST COUNTING
function countTestsInFile($path)
{
    if (!file_exists($path))
        return 0;
    $content = file_get_contents($path);

    // PHPUnit: "public function testMethods()"
    $phpunit = preg_match_all('/public\s+function\s+test\w+/i', $content);

    // Pest: "test('description', ...)" or "it('description', ...)"
    // Matches start of line or after space, followed by test/it, open paren, quote
    $pest = preg_match_all('/(?:^|\s)(?:test|it)\s*\([\'"]/', $content);

    // Attributes: "#[Test]" (PHPUnit 10+)
    $attrs = preg_match_all('/#\[Test\]/i', $content);

    return $phpunit + $pest + $attrs;
}

function scanTestsRecursive($dir)
{
    if (!is_dir($dir))
        return [];

    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $files = [];
    $projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/../../'));
    $totalCount = 0;

    foreach ($iter as $f) {
        if ($f->isFile() && str_ends_with($f->getFilename(), 'Test.php')) {
            $path = str_replace('\\', '/', $f->getRealPath());
            if (str_contains($path, '/vendor/') || str_contains($path, '/Archived/'))
                continue;

            $testCount = countTestsInFile($path);
            if ($testCount === 0)
                continue; // Skip files with no actual tests

            $totalCount += $testCount;

            $rel = str_replace($projectRoot . '/', '', $path);
            $relFromTests = str_replace($projectRoot . '/tests/', '', $path);
            $category = dirname($relFromTests);
            if ($category === '.')
                $category = 'Root';
            $category = str_replace('\\', '/', $category);

            $files[] = [
                'name' => $f->getFilename(),
                'path' => $rel,
                'cat' => $category,
                'count' => $testCount
            ];
        }
    }

    usort($files, fn($a, $b) => $a['cat'] <=> $b['cat'] ?: $a['name'] <=> $b['name']);

    return ['files' => $files, 'total' => $totalCount];
}

// ACTION HANDLER
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $act = $_GET['action'];
    $res = ['status' => 'ok', 'output' => ''];

    try {
        switch ($act) {
            case 'refresh_stats':
                $scan = scanTestsRecursive(__DIR__ . '/../../tests');
                $res['data'] = [
                    'total' => $scan['total'],
                    'timestamp' => date('H:i:s')
                ];
                break;

            case 'run_cmd':
                $input = json_decode(file_get_contents('php://input'), true);
                $cmd = $input['cmd'] ?? '';
                $root = realpath(__DIR__ . '/../../');
                chdir($root);
                $res['output'] = shell_exec($cmd . " 2>&1");
                break;

            case 'git_status':
                $res['data'] = getGitInfo();
                break;

            case 'read_logs':
                $res['data'] = getLogTail();
                break;

            case 'purge_cache':
                $res['data'] = purgeCache();
                break;

            case 'run_test':
                $file = $_GET['file'] ?? '';
                $target = realpath(__DIR__ . '/../../' . $file);
                if ($target && file_exists($target)) {
                    $ext = pathinfo($target, PATHINFO_EXTENSION);
                    $cmd = ($ext === 'php') ? "php \"$target\"" : "\"$target\"";
                    $root = realpath(__DIR__ . '/../../');
                    chdir($root);
                    $res['output'] = shell_exec($cmd . " 2>&1");
                } else {
                    $res['output'] = "Error: File not found.";
                }
                break;
        }
    } catch (Exception $e) {
        $res['status'] = 'error';
        $res['output'] = $e->getMessage();
    }
    echo json_encode($res);
    exit;
}

// INITIAL LOAD
$scan = scanTestsRecursive(__DIR__ . '/../../tests');
$tests = $scan['files'];
$totalTests = $scan['total'];
$groupedTests = [];
foreach ($tests as $t) {
    $groupedTests[$t['cat']][] = $t;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HYPER-GRID // MCAG TOOLKIT</title>
    <!-- FONTS -->
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Rajdhani:wght@400;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/all.min.css">

    <style>
        :root {
            --neon-blue: #00f3ff;
            --neon-pink: #ff00ff;
            --neon-green: #00ff41;
            --void-bg: #050505;
            --grid-color: rgba(0, 243, 255, 0.1);
            --glass-bg: rgba(10, 15, 30, 0.85);
            --crt-scanline: rgba(18, 16, 16, 0.1);
        }

        body {
            background-color: var(--void-bg);
            color: var(--neon-blue);
            font-family: 'Rajdhani', sans-serif;
            margin: 0;
            overflow: hidden;
            height: 100vh;
            display: flex;
            background-image:
                linear-gradient(var(--grid-color) 1px, transparent 1px),
                linear-gradient(90deg, var(--grid-color) 1px, transparent 1px);
            background-size: 50px 50px;
            background-position: center bottom;
            perspective: 1000px;
        }

        /* --- CRT EFFECT --- */
        body::after {
            content: " ";
            display: block;
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.25) 50%), linear-gradient(90deg, rgba(255, 0, 0, 0.06), rgba(0, 255, 0, 0.02), rgba(0, 0, 255, 0.06));
            background-size: 100% 2px, 3px 100%;
            pointer-events: none;
            z-index: 9999;
        }

        /* --- LAYOUT --- */
        .sidebar {
            width: 320px;
            background: var(--glass-bg);
            border-right: 1px solid var(--neon-blue);
            display: flex;
            flex-direction: column;
            backdrop-filter: blur(10px);
            box-shadow: 10px 0 30px rgba(0, 243, 255, 0.1);
            z-index: 100;
        }

        .main-deck {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            padding: 20px;
            gap: 20px;
            position: relative;
        }

        /* --- BRAND --- */
        .brand {
            padding: 20px;
            font-family: 'Share Tech Mono', monospace;
            font-size: 24px;
            text-shadow: 0 0 10px var(--neon-blue);
            border-bottom: 1px solid var(--neon-blue);
            letter-spacing: 2px;
        }

        /* --- MODULES (Sidebar) --- */
        .module-list {
            flex-grow: 1;
            overflow-y: auto;
            padding: 10px;
        }

        .module-list::-webkit-scrollbar {
            width: 4px;
            background: #000;
        }

        .module-list::-webkit-scrollbar-thumb {
            background: var(--neon-blue);
        }

        .module-group-title {
            color: var(--neon-pink);
            font-size: 12px;
            text-transform: uppercase;
            margin: 15px 5px 5px;
            letter-spacing: 1px;
            text-shadow: 0 0 5px var(--neon-pink);
            display: flex;
            justify-content: space-between;
        }

        .module-item {
            padding: 10px 15px;
            margin-bottom: 5px;
            border: 1px solid rgba(0, 243, 255, 0.2);
            background: rgba(0, 0, 0, 0.3);
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: 'Share Tech Mono', monospace;
            font-size: 14px;
        }

        .module-item:hover {
            background: rgba(0, 243, 255, 0.1);
            border-color: var(--neon-blue);
            transform: translateX(5px);
            box-shadow: -5px 0 10px var(--neon-blue);
        }

        .module-item i {
            width: 20px;
        }

        .badge-count {
            font-size: 10px;
            opacity: 0.7;
        }

        /* --- TOP BAR (New Tools) --- */
        .top-bar {
            display: flex;
            gap: 15px;
            padding: 10px;
            background: var(--glass-bg);
            border: 1px solid var(--neon-blue);
            border-radius: 4px;
        }

        .tool-btn {
            background: transparent;
            border: 1px solid var(--neon-blue);
            color: var(--neon-blue);
            padding: 8px 16px;
            font-family: 'Share Tech Mono', monospace;
            cursor: pointer;
            transition: 0.3s;
            text-transform: uppercase;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tool-btn:hover {
            background: var(--neon-blue);
            color: #000;
            box-shadow: 0 0 15px var(--neon-blue);
        }

        .tool-btn.danger {
            border-color: var(--neon-pink);
            color: var(--neon-pink);
        }

        .tool-btn.danger:hover {
            background: var(--neon-pink);
            color: #000;
            box-shadow: 0 0 15px var(--neon-pink);
        }

        /* --- TERMINAL (CRT) --- */
        .terminal-container {
            flex-grow: 1;
            background: #000;
            border: 2px solid var(--neon-green);
            padding: 15px;
            overflow-y: auto;
            font-family: 'Share Tech Mono', monospace;
            color: var(--neon-green);
            text-shadow: 0 0 5px var(--neon-green);
            position: relative;
            box-shadow: inset 0 0 20px rgba(0, 255, 65, 0.2);
        }

        .terminal-output {
            white-space: pre-wrap;
            margin-bottom: 20px;
        }

        .terminal-input-line {
            display: flex;
            gap: 10px;
            align-items: center;
            border-top: 1px solid #333;
            padding-top: 10px;
        }

        .terminal-input {
            background: transparent;
            border: none;
            color: var(--neon-green);
            font-family: 'Share Tech Mono', monospace;
            font-size: 16px;
            flex-grow: 1;
            outline: none;
            text-shadow: 0 0 5px var(--neon-green);
        }

        /* --- GLITCH ANIMATION --- */
        @keyframes glitch {
            0% {
                transform: translate(0)
            }

            20% {
                transform: translate(-2px, 2px)
            }

            40% {
                transform: translate(-2px, -2px)
            }

            60% {
                transform: translate(2px, 2px)
            }

            80% {
                transform: translate(2px, -2px)
            }

            100% {
                transform: translate(0)
            }
        }

        /* --- NEURAL MODE (God UX) --- */
        #neural-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            opacity: 0;
            transition: opacity 1.5s ease-in-out;
        }

        .neural-mode {
            --neon-blue: #00e5ff;
            /* Cyan */
            --neon-pink: #d400d4;
            /* Deep Magenta */
            --neon-green: #ffd700;
            /* Gold */
            --void-bg: #1a0b2e;
            /* Deep Purple Void */
            --grid-color: rgba(120, 81, 169, 0.1);
            --glass-bg: rgba(45, 20, 60, 0.75);
            font-family: 'Rajdhani', sans-serif !important;
            /* Force organic font */
        }

        .neural-mode body {
            background-image: radial-gradient(circle at 50% 50%, #2d143c 0%, #1a0b2e 100%);
            background-size: cover;
        }

        .neural-mode .sidebar {
            background: linear-gradient(180deg, rgba(80, 20, 100, 0.8), rgba(45, 20, 60, 0.8));
            border-right: 1px solid var(--neon-pink);
            border-radius: 0 20px 20px 0;
            /* Organic Shapes */
            box-shadow: 10px 0 50px rgba(212, 0, 212, 0.2);
        }

        .neural-mode .module-item {
            border-radius: 12px;
            border: 1px solid rgba(255, 215, 0, 0.2);
            background: rgba(255, 255, 255, 0.05);
            font-family: 'Rajdhani', sans-serif;
            font-weight: 600;
        }

        .neural-mode .module-item:hover {
            background: rgba(255, 215, 0, 0.15);
            border-color: var(--neon-green);
            transform: scale(1.02);
            /* Breathe effect */
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
        }

        .neural-mode .terminal-container {
            border: 1px solid var(--neon-pink);
            border-radius: 15px;
            background: rgba(20, 10, 30, 0.9);
            box-shadow: inset 0 0 50px rgba(212, 0, 212, 0.1);
        }

        .glitch:hover {
            animation: glitch 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94) both infinite;
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="brand glitch">
            <i class="fa-solid fa-microchip"></i> HYPER-GRID
            <div style="font-size: 10px; color: var(--neon-blue); letter-spacing: 1px; margin-top: 5px;">
                DIAGNOSTIC SYSTEM v7.1
            </div>
        </div>

        <div class="module-list">
            <div class="module-group-title">
                <span>TOTAL TESTS:</span>
                <span id="live-count" style="color: var(--neon-green)"><?= $totalTests ?></span>
            </div>

            <?php foreach ($groupedTests as $category => $modules): ?>
                <div class="module-group-title"
                    style="margin-top: 15px; border-bottom: 1px solid rgba(0,243,255,0.1); padding-bottom: 2px;">
                    <?= htmlspecialchars($category) ?>
                    <span style="font-size: 10px; opacity: 0.5">
                        [<?= array_sum(array_column($modules, 'count')) ?>]
                    </span>
                </div>
                <?php foreach ($modules as $test): ?>
                    <div class="module-item" onclick="runTest('<?= $test['path'] ?>')">
                        <div style="display:flex; align-items:center; gap:8px">
                            <i class="fa-solid fa-vial"></i>
                            <span><?= str_replace('Test.php', '', $test['name']) ?></span>
                        </div>
                        <span class="badge-count"><?= $test['count'] ?>t</span>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <div class="module-group-title"
                style="margin-top: 30px; border-top: 1px dashed var(--neon-pink); padding-top: 10px;">System Core</div>
            <div class="module-item" onclick="fetchGitStatus()">
                <i class="fa-brands fa-git-alt"></i> GIT STATUS
            </div>
            <div class="module-item" onclick="fetchLogs()">
                <i class="fa-solid fa-scroll"></i> LOG INTERCEPTOR
            </div>
        </div>
    </div>

    <!-- MAIN DECK -->
    <div class="main-deck">
        <!-- TOOLBAR (New Features) -->
        <div class="top-bar">
            <button class="tool-btn" onclick="fetchGitStatus()"><i class="fa-solid fa-code-branch"></i> Git
                Check</button>
            <button class="tool-btn" onclick="fetchLogs()"><i class="fa-solid fa-align-left"></i> Tail Logs</button>
            <button class="tool-btn" onclick="window.location.reload()"><i class="fa-solid fa-rotate"></i>
                Reboot</button>
            <div style="flex-grow: 1"></div>
            <!-- GOD MODE TOGGLE -->
            <button class="tool-btn"
                style="border-color: var(--neon-green); color: var(--neon-green); margin-right: 10px;"
                onclick="toggleMode()">
                <i class="fa-solid fa-brain"></i> NEURAL LINK
            </button>
            <button class="tool-btn danger" onclick="purgeCache()"><i class="fa-solid fa-trash-can"></i> PURGE
                CACHE</button>
        </div>

        <!-- TERMINAL -->
        <div class="terminal-container" id="terminal">
            <div class="terminal-output" id="output">
                > HYPER-GRID SYSTEM INITIALIZED...
                > SCANNING FOR TESTS... DETECTED [<?= $totalTests ?>].
                > READY FOR INPUT.
                > _
            </div>
            <div class="terminal-input-line">
                <span>admin@hypergrid:~$</span>
                <input type="text" class="terminal-input" id="cmdInput" autofocus placeholder="Enter command...">
            </div>
        </div>
    </div>

    <!-- NEURAL CANVAS -->
    <canvas id="neural-canvas"></canvas>

    <script>
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

                const res = await fetch(url, action === 'run_cmd' ? opts : undefined);
                const json = await res.json();

                if (json.data) {
                    if (action === 'refresh_stats') {
                        countEl.innerText = json.data.total;
                        // Only log if changed? Nah excessive logging is cool.
                        // Actually, let's NOT log stats refresh to keep terminal clean.
                        return;
                    }
                    log(JSON.stringify(json.data, null, 2), 'success');
                } else if (json.output) {
                    log(json.output);
                }
            } catch (e) {
                log(e.message, 'error');
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

        function purgeCache() {
            if (confirm('WARNING: THIS WILL NUKE SYSTEM CACHE. PROCEED?')) {
                log('INITIATING CACHE PURGE PROTOCOL...');
                apiCall('purge_cache');
            }
        }

        // CMD Input
        cmdInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                const cmd = cmdInput.value;
                if (!cmd) return;

                log(`> ${cmd}`);
                apiCall('run_cmd', { cmd });
                cmdInput.value = '';
            }
        });

    </script>
</body>

</html>