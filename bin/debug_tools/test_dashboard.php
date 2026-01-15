<?php
/**
 * MCAG HYPER-GRID TOOLKIT v7.0
 * "QUANTUM ENGINEERING DECK"
 * 
 * @version 7.0.0-hypergrid
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
    // Efficient tail for large files
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

// ACTION HANDLER
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $act = $_GET['action'];
    $res = ['status' => 'ok', 'output' => ''];

    try {
        switch ($act) {
            case 'run_cmd':
                $input = json_decode(file_get_contents('php://input'), true);
                $cmd = $input['cmd'] ?? '';
                // [Security Placeholder: In prod, strictly validate allowlist]
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
                // Simple Runner wrapper
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

// --- DATA PREP ---
function scanTests($dir) {
    if (!is_dir($dir)) return [];
    
    // Use RecursiveDirectoryIterator directly
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    $files = [];
    $projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/../../'));

    foreach ($iter as $f) {
        // Only PHP Test files
        if ($f->isFile() && str_ends_with($f->getFilename(), 'Test.php')) {
            $path = str_replace('\\', '/', $f->getRealPath());
            
            // Exclude archived/vendor
            if (str_contains($path, '/vendor/') || str_contains($path, '/Archived/')) continue;

            $rel = str_replace($projectRoot . '/', '', $path);
            
            // Category: Relative path from tests/ (e.g. "Feature/Admin")
            // Assuming tests are in $projectRoot/tests/
            $relFromTests = str_replace($projectRoot . '/tests/', '', $path);
            $category = dirname($relFromTests);
            if ($category === '.') $category = 'Root';

            // Sanitize path separators in category
            $category = str_replace('\\', '/', $category);

            $files[] = [
                'name' => $f->getFilename(), 
                'path' => $rel, 
                'cat' => $category
            ];
        }
    }
    
    // Sort modules by Category then Name
    usort($files, fn($a, $b) => $a['cat'] <=> $b['cat'] ?: $a['name'] <=> $b['name']);
    
    return $files;
}

$tests = scanTests(__DIR__ . '/../../tests');
$totalTests = count($tests);

// Grouping for sidebar
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
        </div>
        <div class="module-list">
            <div class="module-group-title">Diagnostic Modules</div>
            <?php foreach ($tests as $test): ?>
                <div class="module-item" onclick="runTest('<?= $test['path'] ?>')">
                    <i class="fa-solid fa-vial"></i>
                    <span><?= $test['name'] ?></span>
                </div>
            <?php endforeach; ?>

            <div class="module-group-title">System Core</div>
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
            <button class="tool-btn danger" onclick="purgeCache()"><i class="fa-solid fa-trash-can"></i> PURGE
                CACHE</button>
        </div>

        <!-- TERMINAL -->
        <div class="terminal-container" id="terminal">
            <div class="terminal-output" id="output">
                > HYPER-GRID SYSTEM INITIALIZED...
                > READY FOR INPUT.
                > _
            </div>
            <div class="terminal-input-line">
                <span>admin@hypergrid:~$</span>
                <input type="text" class="terminal-input" id="cmdInput" autofocus placeholder="Enter command...">
            </div>
        </div>
    </div>

    <script>
        const outputEl = document.getElementById('output');
        const cmdInput = document.getElementById('cmdInput');

        function log(text, type = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            let prefix = `[${timestamp}] `;
            if (type === 'error') prefix += '[ERR] ';
            if (type === 'success') prefix += '[OK] ';

            outputEl.innerText += '\n' + prefix + text;
            // Scroll to bottom
            document.getElementById('terminal').scrollTop = document.getElementById('terminal').scrollHeight;
        }

        async function apiCall(action, data = {}) {
            log(`EXECUTING: ${action}...`);
            try {
                let url = `?action=${action}`;
                if (action === 'run_test') url += `&file=${data.file}`;

                const opts = { method: 'POST', body: JSON.stringify(data) };
                if (action === 'run_test' || action === 'git_status' || action === 'read_logs' || action === 'purge_cache') {
                    // these use GET or don't need body for minimal impl, but fetch handles it.
                    // Actually, my PHP expects GET for action, but POST body for run_cmd. 
                    // Let's standardise on query param for action.
                }

                const res = await fetch(url, action === 'run_cmd' ? opts : undefined);
                const json = await res.json();

                if (json.data) {
                    log(JSON.stringify(json.data, null, 2), 'success');
                } else if (json.output) {
                    log(json.output);
                }
            } catch (e) {
                log(e.message, 'error');
            }
        }

        function runTest(file) {
            apiCall('run_test', { file });
        }

        function fetchGitStatus() {
            apiCall('git_status');
        }

        function fetchLogs() {
            apiCall('read_logs');
        }

        function purgeCache() {
            if (confirm('CONFIRM PURGE?')) apiCall('purge_cache');
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

        // Intro Effect
        setTimeout(() => {
            log("ESTABLISHING UPLINK...", "success");
            log("ACCESS GRANTED.", "success");
        }, 500);

    </script>
</body>

</html>