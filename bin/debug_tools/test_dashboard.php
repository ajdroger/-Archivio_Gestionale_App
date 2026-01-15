<?php
/**
 * MCAG System - Test & Automation Dashboard (Compact Grid Edition)
 * @version 5.4.5
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// Set Timezone to match User Context (Rome)
date_default_timezone_set('Europe/Rome');

// Secure Session Start (Matches Middleware)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_path', '/');
    ini_set('session.gc_maxlifetime', 3600);

    // Check HTTPS
    $secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    ini_set('session.cookie_secure', $secure ? 1 : 0);

    session_start();
}

// --- ACTION HANDLING (AJAX) ---
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    if ($action === 'run_cmd') {
        $input = json_decode(file_get_contents('php://input'), true);
        $cmd = trim($input['cmd'] ?? '');
        $response = ['output' => ''];

        switch (strtolower($cmd)) {
            case 'help':
                $response['output'] = "Available commands:\n  run all       - Run complete test suite\n  run <file>    - Run specific test file\n  list          - List available test suites\n  cls/clear     - Clear console\n  whoami        - Display current user";
                break;
            case 'cls':
            case 'clear':
                $response['output'] = '__CLEAR__';
                break;
            case 'list':
                $files = getTestFiles(__DIR__ . '/../../tests');
                $list = array_map(fn($f) => " - " . $f['name'] . " (" . $f['category'] . ")", $files['files']);
                $response['output'] = "Available Tests:\n" . implode("\n", array_slice($list, 0, 20)) . (count($list) > 20 ? "\n...and " . (count($list) - 20) . " more." : "");
                break;
            case 'run all':
                // Trigger full run via passthru to capture streaming output?
                // For JSON response, we use exec/shell_exec.
                $response['output'] = "Initiating full test suite...\nWarning: This may take a while.\n[EXECUTION STARTED]";
                break;
            case 'whoami':
                $response['output'] = "User: " . get_current_user() . "\nRole: Administrator (Simulated)";
                break;
            default:
                if (str_starts_with($cmd, 'run ')) {
                    // Handled by frontend usually, but if sent here:
                    $response['output'] = "Executing custom run: " . substr($cmd, 4);
                } else {
                    $response['output'] = "Command not recognized: '$cmd'. Type 'help' for instructions.";
                }
        }
        echo json_encode($response);
        exit;
    }

    if ($action === 'run_test') {
        $file = $_GET['file'] ?? '';
        $verbose = isset($_GET['verbose']) && $_GET['verbose'] === 'true';

        // Security Sanity Check (Prevent ../ traversal outside project)
        $realBase = realpath(__DIR__ . '/../../');
        $target = realpath($realBase . '/' . $file);

        if (!$target || !str_starts_with($target, $realBase)) {
            echo "Error: Invalid file path security violation.";
            exit;
        }

        // Determine command based on extension
        $cmd = '';
        $ext = strtolower(pathinfo($target, PATHINFO_EXTENSION));

        switch ($ext) {
            case 'php':
                if (str_contains($file, 'bin/') || str_contains($file, 'debug_tools/') || str_contains($file, 'src/Debug/')) {
                    $cmd = "php \"$target\"";
                } else {
                    // Test Logic (Pest/PHPUnit)
                    // Fix: Use simple execution to avoid XML config errors if arg order is wrong
                    $bin = $realBase . '/vendor/bin/pest';
                    if (!file_exists($bin))
                        $bin = $realBase . '/vendor/bin/phpunit';

                    // Windows Safe Wrapper
                    // Force colors for ANSI parsing
                    // Use --testdox for better readability if verbose
                    $cmd = "php \"$bin\" --colors=always " . ($verbose ? "--testdox " : "") . "\"$target\"";
                }
                break;
            case 'py':
                $cmd = "python \"$target\"";
                break;
            case 'js':
                $cmd = "node \"$target\"";
                break;
            case 'ts':
                $cmd = "ts-node \"$target\"";
                break;
            case 'java':
                // Java 11+ Single-File Source Code Support
                $cmd = "java \"$target\"";
                break;
            case 'go':
                $cmd = "go run \"$target\"";
                break;
            case 'rb':
                $cmd = "ruby \"$target\"";
                break;
            case 'ps1':
                $cmd = "powershell -ExecutionPolicy Bypass -File \"$target\"";
                break;
            case 'bat':
            case 'cmd':
                $cmd = "\"$target\"";
                break;
            case 'sh':
                $cmd = "bash \"$target\""; // Windows Git Bash or WSL assumption
                break;
            default:
                echo "Error: Unsupported file type '.$ext'";
                exit;
        }

        // Execute and capture output
        // We want to capture stderr too 2>&1
        $output = [];
        $returnVar = 0;
        exec($cmd . " 2>&1", $output, $returnVar);

        // Convert ANSI colors to HTML if needed, or raw text wrapped in pre
        echo implode("\n", $output);
        exit;
    }
}
function countTestsInFile($path)
{
    if (!file_exists($path))
        return 0;
    $content = file_get_contents($path);
    $phpunitCount = preg_match_all('/public\s+function\s+test\w+/i', $content);
    $pestCount = preg_match_all('/(test|it)\s*\(/', $content);
    // Attribute support
    $attrCount = preg_match_all('/#\[Test\]/i', $content); // PHPUnit 10+
    return $phpunitCount + $pestCount + $attrCount;
}


function getTestFiles($dir)
{
    if (!is_dir($dir))
        return ['files' => [], 'total' => 0];

    // Recursive Search
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    $projectRoot = realpath(__DIR__ . '/../../');

    foreach ($iterator as $file) {
        if ($file->isDir())
            continue;
        if ($file->getExtension() !== 'php')
            continue;

        // Match Test Files (ends with Test.php or PEST convention)
        if (!str_contains($file->getFilename(), 'Test.php') && !str_contains($file->getFilename(), 'test.php')) {
            // Check content for Pest "it(" or "test(" if filename is vague? 
            // Stick to filename convention for speed, assuming standard naming.
            continue;
        }

        $path = str_replace('\\', '/', $file->getRealPath());

        // Exclusions
        if (str_contains($path, '/vendor/') || str_contains($path, '/Archived/') || str_contains($path, '/Archive/')) {
            continue;
        }

        $relPath = str_replace(str_replace('\\', '/', $projectRoot) . '/', '', $path);
        $category = basename(dirname($path));

        // Root suite fix
        if ($category === 'tests')
            $category = 'Root Suite';

        $files[] = [
            'name' => $file->getFilename(),
            'rel_path' => $relPath,
            'category' => $category,
            'count' => countTestsInFile($path)
        ];
    }

    // Check for Pest tests that might not have "Test" in filename but are in tests/
    // (Optional: PEST often uses *Test.php anyway, but let's be safe)

    $total = array_sum(array_column($files, 'count'));
    return ['files' => $files, 'total' => $total];
}

function getBinScripts($ignoredDir = null)
{
    $projectRoot = realpath(__DIR__ . '/../../');
    // Define folders to scan for runnable scripts
    $scanTargets = [
        $projectRoot . '/bin',
        $projectRoot . '/src/Debug'
    ];

    $scripts = [];

    foreach ($scanTargets as $dir) {
        if (!is_dir($dir))
            continue;

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST,
                RecursiveIteratorIterator::CATCH_GET_CHILD
            );

            foreach ($iterator as $file) {
                if ($file->isDir())
                    continue;

                $ext = $file->getExtension();
                if (!in_array($ext, ['php', 'ps1', 'sh', 'bat', 'cmd', 'py', 'java', 'js', 'ts', 'go', 'rb']))
                    continue;

                $path = str_replace('\\', '/', $file->getRealPath());

                // Exclusions
                if (str_contains($path, 'test_dashboard.php'))
                    continue;
                if (str_contains($path, 'safe_test_runner.php'))
                    continue;

                // Relative path logic
                $relPath = str_replace(str_replace('\\', '/', $projectRoot) . '/', '', $path);

                // Display Name logic (simplify)
                $name = $file->getFilename();
                $relDir = dirname($relPath);

                // If deep in structure, show folder context
                if ($relDir !== 'bin' && $relDir !== 'src/Debug') {
                    // e.g. bin/tools -> tools/script.php
                    // src/Debug/Auth -> Auth/script.php
                    $shortDir = basename($relDir);
                    $name = "$shortDir/$name";
                }

                $scripts[] = [
                    'name' => $name,
                    'rel_path' => $relPath,
                    'type' => $ext,
                    'last_mod' => date('d/m H:i', $file->getMTime())
                ];
            }
        } catch (Exception $e) {
            // Fallback for this dir
            $fallback = glob($dir . '/*.{php,py,js,sh,bat,cmd,ps1}', GLOB_BRACE);
            if ($fallback) {
                foreach ($fallback as $f) {
                    if (str_contains($f, 'test_dashboard.php'))
                        continue;
                    $scripts[] = [
                        'name' => basename($f),
                        'rel_path' => str_replace($projectRoot . '/', '', str_replace('\\', '/', $f)),
                        'type' => 'php',
                        'last_mod' => date('d/m H:i', filemtime($f))
                    ];
                }
            }
        }
    }

    // Sort by modification time desc
    usort($scripts, fn($a, $b) => strcmp($b['last_mod'], $a['last_mod']));

    return $scripts;
}

$testData = getTestFiles(__DIR__ . '/../../tests');
$testFiles = $testData['files'] ?? [];
$totalTestsCount = $testData['total'] ?? 0;

$binScripts = getBinScripts(__DIR__ . '/..');

$grouped = [];
foreach ($testFiles as $tf) {
    $grouped[$tf['category']][] = $tf;
}
ksort($grouped);
?>
<!DOCTYPE html>
<html lang="it" class="h-100">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MCAG Toolkit v5.5 | Enterprise Edition</title>

    <!-- Fonts: Inter (UI), JetBrains Mono (Code) -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/all.min.css">

    <style>
        :root {
            /* Enterprise Palette - Dark Slate/Charcoal */
            --ent-bg-main: #0f172a;
            /* Slate 900 */
            --ent-bg-panel: #1e293b;
            /* Slate 800 */
            --ent-bg-hover: #334155;
            /* Slate 700 */
            --ent-border: #334155;
            --ent-text-main: #f8fafc;
            /* Slate 50 */
            --ent-text-muted: #94a3b8;
            /* Slate 400 */

            /* Accents - Professional & Restrained */
            --ent-accent-blue: #3b82f6;
            /* Primary Action */
            --ent-accent-amber: #f59e0b;
            /* Warning/Scripts */
            --ent-accent-red: #ef4444;
            /* Critical/Emergency */
            --ent-accent-green: #10b981;
            /* Success */

            --ent-font-ui: 'Inter', system-ui, -apple-system, sans-serif;
            --ent-font-code: 'JetBrains Mono', monospace;
            --ent-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --ent-drawer-height: 400px;
        }

        body {
            background-color: var(--ent-bg-main);
            color: var(--ent-text-main);
            font-family: var(--ent-font-ui);
            overflow-x: hidden;
            margin: 0;
            padding-bottom: 80px;
            /* Space for drawer handle */
        }

        /* --- Top Navigation Bar --- */
        .ent-navbar {
            background: rgba(15, 23, 42, 0.95);
            border-bottom: 1px solid var(--ent-border);
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(8px);
        }

        .ent-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            letter-spacing: -0.01em;
            font-size: 1.1rem;
            color: var(--ent-text-main);
        }

        .ent-brand i {
            color: var(--ent-accent-blue);
        }

        .ent-version {
            font-size: 0.75rem;
            color: var(--ent-text-muted);
            background: var(--ent-bg-panel);
            padding: 2px 8px;
            border-radius: 4px;
            font-family: var(--ent-font-code);
        }

        /* --- Emergency/Action Menu --- */
        .ent-actions {
            position: relative;
        }

        .btn-emergency {
            background: transparent;
            border: 1px solid var(--ent-border);
            color: var(--ent-text-muted);
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-emergency:hover,
        .btn-emergency.active {
            background: var(--ent-bg-hover);
            color: var(--ent-text-main);
            border-color: var(--ent-text-muted);
        }

        .btn-emergency i.fa-chevron-down {
            font-size: 0.7rem;
            transition: transform 0.2s;
        }

        .btn-emergency.active i.fa-chevron-down {
            transform: rotate(180deg);
        }

        .ent-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 8px;
            width: 280px;
            background: var(--ent-bg-panel);
            border: 1px solid var(--ent-border);
            border-radius: 8px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1001;
            overflow: hidden;
        }

        .ent-dropdown.open {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .ent-menu-header {
            padding: 12px 16px;
            background: #00000030;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--ent-text-muted);
            font-weight: 700;
            border-bottom: 1px solid var(--ent-border);
        }

        .ent-menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--ent-text-main);
            text-decoration: none;
            transition: background 0.15s;
            font-size: 0.9rem;
            border-bottom: 1px solid #ffffff05;
            background: none;
            width: 100%;
            border: none;
            text-align: left;
            cursor: pointer;
        }

        .ent-menu-item:hover {
            background: var(--ent-bg-hover);
        }

        .ent-menu-item:last-child {
            border-bottom: none;
        }

        .ent-menu-item i {
            width: 20px;
            text-align: center;
            color: var(--ent-text-muted);
        }

        .ent-menu-item:hover i {
            color: var(--ent-text-main);
        }

        .ent-menu-item.danger i {
            color: var(--ent-accent-red);
        }

        /* --- Header Stats --- */
        .ent-stats-bar {
            background: var(--ent-bg-panel);
            border-bottom: 1px solid var(--ent-border);
            padding: 24px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 24px;
        }

        .ent-stat-card {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .ent-stat-value {
            font-size: 1.75rem;
            font-weight: 300;
            font-family: var(--ent-font-code);
            line-height: 1;
        }

        .ent-stat-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--ent-text-muted);
            font-weight: 600;
        }

        /* --- Grid Layout --- */
        .ent-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 24px;
            padding: 24px;
        }

        .ent-card {
            background: var(--ent-bg-panel);
            border: 1px solid var(--ent-border);
            border-radius: 6px;
            display: flex;
            flex-direction: column;
            transition: border-color 0.2s;
        }

        .ent-card:hover {
            border-color: var(--ent-text-muted);
        }

        .ent-card-header {
            padding: 16px;
            border-bottom: 1px solid var(--ent-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.02);
        }

        .ent-card-title {
            font-family: var(--ent-font-ui);
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--ent-text-main);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .ent-card-body {
            padding: 0;
            max-height: 320px;
            overflow-y: auto;
        }

        /* Custom Scrollbar */
        .ent-card-body::-webkit-scrollbar {
            width: 6px;
        }

        .ent-card-body::-webkit-scrollbar-track {
            background: var(--ent-bg-panel);
        }

        .ent-card-body::-webkit-scrollbar-thumb {
            background: var(--ent-border);
            border-radius: 3px;
        }

        .ent-card-body::-webkit-scrollbar-thumb:hover {
            background: var(--ent-text-muted);
        }

        .ent-list-item {
            padding: 12px 16px;
            border-bottom: 1px solid var(--ent-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.1s;
        }

        .ent-list-item:last-child {
            border-bottom: none;
        }

        .ent-list-item:hover {
            background: var(--ent-bg-hover);
        }

        .ent-item-name {
            font-family: var(--ent-font-code);
            font-size: 0.85rem;
            color: #cbd5e1;
        }

        .ent-btn-icon {
            background: transparent;
            border: 1px solid var(--ent-border);
            color: var(--ent-text-muted);
            width: 28px;
            height: 28px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.1s;
        }

        .ent-btn-icon:hover {
            background: var(--ent-accent-blue);
            border-color: var(--ent-accent-blue);
            color: white;
        }

        /* --- Draggable Console (Termux Style) --- */
        #console-drawer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: var(--ent-drawer-height);
            background: #000;
            /* Deep Black for terminal */
            border-top: 1px solid var(--ent-border);
            z-index: 2000;
            transform: translateY(calc(100% - 32px));
            /* Show only handle */
            transition: transform 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
            display: flex;
            flex-direction: column;
            box-shadow: 0 -10px 20px rgba(0, 0, 0, 0.5);
        }

        #console-drawer.open {
            transform: translateY(0);
        }

        #console-drawer.maximized {
            height: 100% !important;
            transform: translateY(0);
        }

        .drawer-handle-bar {
            height: 32px;
            background: var(--ent-bg-panel);
            border-bottom: 1px solid var(--ent-border);
            cursor: grab;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .drawer-handle-bar:active {
            cursor: grabbing;
        }

        .handle-pill {
            width: 40px;
            height: 4px;
            background: var(--ent-border);
            border-radius: 2px;
        }

        .console-controls {
            position: absolute;
            right: 12px;
            top: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        #console-content {
            flex-grow: 1;
            padding: 16px;
            font-family: var(--ent-font-code);
            font-size: 0.85rem;
            color: #ccc;
            overflow-y: auto;
            white-space: pre-wrap;
        }

        .cmd-line {
            display: flex;
            gap: 8px;
            margin-top: 8px;
            padding: 0 16px 16px;
            border-top: 1px solid #333;
            padding-top: 8px;
        }

        .cmd-prompt {
            color: var(--ent-accent-green);
            font-weight: bold;
        }

        .cmd-input {
            background: transparent;
            border: none;
            color: white;
            font-family: var(--ent-font-code);
            flex-grow: 1;
            outline: none;
        }

        /* --- Settings Modal (Simple Enterprise) --- */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            z-index: 3000;
            display: none;
            justify-content: center;
            align-items: center;
        }

        .modal-overlay.show {
            display: flex;
        }

        .ent-modal {
            background: var(--ent-bg-panel);
            border: 1px solid var(--ent-border);
            border-radius: 8px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .ent-modal-header {
            padding: 16px 24px;
            border-bottom: 1px solid var(--ent-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .ent-modal-body {
            padding: 24px;
        }

        .ent-modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--ent-border);
            display: flex;
            justify-content: flex-end;
        }

        .switch-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="ent-navbar">
        <div class="ent-brand">
            <i class="fa-solid fa-layer-group"></i>
            <span>MCAG TOOLKIT</span>
            <span class="ent-version">v5.5.1 (<?php echo date('H:i:s'); ?>)</span>
        </div>

        <!-- EMERGENCY / SYSTEM ACTIONS -->
        <div class="ent-actions">
            <button class="btn-emergency" id="system-actions-btn" onclick="toggleMenu()">
                SYSTEM ACTIONS <i class="fa-solid fa-chevron-down"></i>
            </button>
            <div class="ent-dropdown" id="system-menu">
                <div class="ent-menu-header">Critical Operations</div>

                <a href="#" class="ent-menu-item" onclick="runAll(); closeMenu(); return false;">
                    <i class="fa-solid fa-play-circle" style="color: var(--ent-accent-green)"></i> Run All Tests
                </a>
                <button class="ent-menu-item" onclick="clearLog(); closeMenu();">
                    <i class="fa-solid fa-eraser"></i> Clear Console
                </button>

                <div class="ent-menu-header">Navigation</div>
                <?php
                $baseUrl = (function () {
                    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
                    $rootPath = str_replace('/bin/debug_tools', '', $scriptDir);
                    return rtrim($rootPath, '/') . '/public';
                })();
                ?>
                <a href="<?php echo $baseUrl; ?>/" class="ent-menu-item">
                    <i class="fa-solid fa-home"></i> Dashboard Home
                </a>
                <a href="<?php echo $baseUrl; ?>/devtools" class="ent-menu-item">
                    <i class="fa-solid fa-toolbox"></i> DevTools
                </a>

                <div class="ent-menu-header">Configuration</div>
                <button class="ent-menu-item" onclick="openSettings(); closeMenu();">
                    <i class="fa-solid fa-sliders"></i> Toolkit Settings
                </button>
                <a href="<?php echo $baseUrl; ?>/impostazioni" class="ent-menu-item">
                    <i class="fa-solid fa-cog"></i> System Settings
                </a>
            </div>
        </div>
    </nav>

    <!-- KEY METRICS -->
    <div class="ent-stats-bar">
        <div class="ent-stat-card">
            <div class="ent-stat-value" style="color: var(--ent-accent-blue)"><?php echo $totalTestsCount; ?></div>
            <div class="ent-stat-label">Total Tests</div>
        </div>
        <div class="ent-stat-card">
            <div class="ent-stat-value" style="color: var(--ent-accent-amber)"><?php echo count($binScripts); ?></div>
            <div class="ent-stat-label">Automation Scripts</div>
        </div>
        <div class="ent-stat-card">
            <div class="ent-stat-value" style="color: var(--ent-accent-green)"><?php echo count($grouped); ?></div>
            <div class="ent-stat-label">Active Suites</div>
        </div>
    </div>

    <!-- MAIN GRID -->
    <main class="ent-grid">

        <!-- Automation & Debug Panel -->
        <div class="ent-card" style="border-top: 3px solid var(--ent-accent-amber);">
            <div class="ent-card-header">
                <div class="ent-card-title">
                    <i class="fa-solid fa-bolt text-warning"></i> Automation & Debug
                </div>
                <!-- Controls -->
                <button class="ent-btn-icon" onclick="runAll()" title="Run All"><i
                        class="fa-solid fa-play"></i></button>
            </div>
            <div class="ent-card-body">
                <?php if (empty($binScripts)): ?>
                    <div style="padding:16px; color:var(--ent-text-muted); text-align:center;">
                        No automation scripts found.<br>
                        <small>Scanned: bin/, src/Debug/</small>
                    </div>
                <?php else: ?>
                    <?php foreach ($binScripts as $script): ?>
                        <div class="ent-list-item">
                            <div>
                                <div class="ent-item-name"><?php echo $script['name']; ?></div>
                                <div style="font-size: 0.7rem; color: var(--ent-text-muted);">
                                    <?php echo $script['last_mod']; ?> • <?php echo $script['type']; ?>
                                </div>
                            </div>
                            <button class="ent-btn-icon" onclick="runTest('<?php echo $script['rel_path']; ?>')"><i
                                    class="fa-solid fa-caret-right"></i></button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Test Suites Loop -->
        <?php foreach ($grouped as $cat => $tests): ?>
            <div class="ent-card" style="border-top: 3px solid var(--ent-accent-blue);">
                <div class="ent-card-header">
                    <div class="ent-card-title">
                        <i class="fa-regular fa-folder-open text-primary"></i> <?php echo $cat; ?>
                    </div>
                    <span
                        style="font-size: 0.75rem; background: rgba(59, 130, 246, 0.1); color: var(--ent-accent-blue); padding: 2px 6px; border-radius: 4px;"><?php echo count($tests); ?></span>
                </div>
                <div class="ent-card-body">
                    <?php foreach ($tests as $t): ?>
                        <div class="ent-list-item">
                            <div class="ent-item-name text-truncate" style="max-width: 200px;"
                                title="<?php echo $t['name']; ?>"><?php echo $t['name']; ?></div>
                            <div class="d-flex align-items-center gap-2">
                                <span style="font-size: 0.7rem; color: var(--ent-text-muted);"><?php echo $t['count']; ?>
                                    tests</span>
                                <button class="ent-btn-icon" onclick="runTest('<?php echo $t['rel_path']; ?>')"><i
                                        class="fa-solid fa-flask"></i></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

    </main>

    <!-- DRAGGABLE CONSOLE DRAWER -->
    <div id="console-drawer">
        <div class="drawer-handle-bar" id="console-handle">
            <div class="handle-pill"></div>
            <div class="console-controls">
                <button class="ent-btn-icon" onclick="clearLog()" style="border:none; height:24px; width:24px;"
                    title="Clear"><i class="fa-solid fa-eraser"></i></button>
                <button class="ent-btn-icon" onclick="toggleMaximize()" style="border:none; height:24px; width:24px;"
                    title="Maximize"><i class="fa-solid fa-expand" id="icon-max"></i></button>
                <button class="ent-btn-icon" onclick="toggleConsole()" style="border:none; height:24px; width:24px;"
                    title="Close"><i class="fa-solid fa-chevron-down"></i></button>
            </div>
        </div>
        <div id="console-content">
            // MCAG Enterprise Toolkit v5.5 initialized.
            // Ready for execution.
        </div>
        <div class="cmd-line">
            <span class="cmd-prompt">ADMIN@MCAG:~#</span>
            <input type="text" class="cmd-input" placeholder="Enter command..." disabled
                title="Read-only in compact mode">
        </div>
    </div>

    <!-- SETTINGS MODAL -->
    <div class="modal-overlay" id="settings-modal">
        <!-- ... existing settings modal ... -->
        <div class="ent-modal">
            <div class="ent-modal-header">
                <div class="ent-card-title"><i class="fa-solid fa-sliders"></i> Configuration</div>
                <button class="ent-btn-icon" onclick="closeSettings()"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="ent-modal-body">
                <div class="switch-row">
                    <div>
                        <div style="font-weight:600;">Verbose Mode</div>
                        <div style="font-size:0.8rem; color:var(--ent-text-muted);">Show detailed output logs</div>
                    </div>
                    <input type="checkbox" id="setting-verbose">
                </div>
                <div class="switch-row">
                    <div>
                        <div style="font-weight:600;">Stop On Failure</div>
                        <div style="font-size:0.8rem; color:var(--ent-text-muted);">Halt execution on first error</div>
                    </div>
                    <input type="checkbox" id="setting-stop-failure">
                </div>
                <div class="switch-row">
                    <div>
                        <div style="font-weight:600;">Auto-Clear Console</div>
                        <div style="font-size:0.8rem; color:var(--ent-text-muted);">Clear output before run</div>
                    </div>
                    <input type="checkbox" id="setting-auto-clear" checked>
                </div>
            </div>
            <div class="ent-modal-footer">
                <button class="btn-emergency" onclick="closeSettings()"
                    style="background: var(--ent-accent-blue); color:white; border:none;">SAVE CHANGES</button>
            </div>
        </div>
    </div>

    <script>
        function closeOutputModal() {
            // Function retained for compatibility if called, but does nothing or logs warning
            // document.getElementById('outputModal').style.display = 'none';
        }
    </script>

    <!-- Scripts (Bootstrap for util if needed, Custom Logic) -->
    <script src="../../public/js/components/toolkit.js?v=<?php echo time(); ?>"></script>
    <script>
        // --- Emergency Menu Logic ---
        function toggleMenu() {
            const menu = document.getElementById('system-menu');
            const btn = document.getElementById('system-actions-btn');
            menu.classList.toggle('open');
            btn.classList.toggle('active');
        }

        function closeMenu() {
            document.getElementById('system-menu').classList.remove('open');
            document.getElementById('system-actions-btn').classList.remove('active');
        }

        // Close menu when clicking outside
        document.addEventListener('click', function (e) {
            const menu = document.querySelector('.ent-actions');
            if (menu && !menu.contains(e.target)) {
                closeMenu();
            }
        });

        // --- Console Logic (Draggable) ---
        // MOVED TO toolkit.js 
        // This duplicate block is removed to prevent conflicts.

        // --- Settings Modal ---
        function openSettings() {
            document.getElementById('settings-modal').classList.add('show');
        }
        function closeSettings() {
            document.getElementById('settings-modal').classList.remove('show');
        }
    </script>
</body>

</html>