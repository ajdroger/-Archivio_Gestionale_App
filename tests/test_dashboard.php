<?php
/**
 * Fratellanza Militare - Test & Automation Dashboard (Compact Grid Edition)
 */

require_once __DIR__ . '/../vendor/autoload.php';

// --- BACKEND LOGIC ---
function countTestsInFile($path)
{
    if (!file_exists($path))
        return 0;
    $content = file_get_contents($path);
    $phpunitCount = preg_match_all('/public\s+function\s+test\w+/i', $content);
    $pestCount = preg_match_all('/(test|it)\s*\(/', $content);
    return $phpunitCount + $pestCount;
}

function getTestFiles($dir)
{
    if (!is_dir($dir))
        return ['files' => [], 'total' => 0];
    $it = new RecursiveDirectoryIterator($dir);
    $display = [];
    $totalTests = 0;

    foreach (new RecursiveIteratorIterator($it) as $file) {
        if ($file->getExtension() === 'php' && strpos($file->getFilename(), 'Test') !== false) {
            $path = str_replace('\\', '/', $file->getPathname());
            $relPath = str_replace(str_replace('\\', '/', __DIR__) . '/', '', $path);
            $category = basename(dirname($path));
            if ($category == 'tests')
                $category = 'Root';

            $testCount = countTestsInFile($path);
            $totalTests += $testCount;

            $display[] = [
                'name' => $file->getFilename(),
                'rel_path' => 'tests/' . $relPath,
                'category' => $category,
                'count' => $testCount
            ];
        }
    }
    return ['files' => $display, 'total' => $totalTests];
}

function getBinScripts($dir)
{
    if (!is_dir($dir))
        return [];
    $files = glob($dir . '/*.{php,ps1}', GLOB_BRACE);
    return array_map(function ($f) {
        return [
            'name' => basename($f),
            'rel_path' => 'bin/' . basename($f),
            'type' => pathinfo($f, PATHINFO_EXTENSION),
            'last_mod' => date('d/m H:i', filemtime($f)) // Compact date
        ];
    }, $files);
}

$testData = getTestFiles(__DIR__);
$testFiles = $testData['files'];
$totalTestsCount = $testData['total'];

$binScripts = getBinScripts(__DIR__ . '/../bin');

$grouped = [];
foreach ($testFiles as $tf) {
    $grouped[$tf['category']][] = $tf;
}
ksort($grouped);
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>Toolkit Compact - Fratellanza Militare</title>
    <link rel="stylesheet" href="/fratellanza-militare-archivio/public/css/premium.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --bg-dark: #0f172a;
            --bg-card: #1e293b;
            --border-color: rgba(255, 255, 255, 0.1);
            --primary: #3b82f6;
            --text-main: #e2e8f0;
            --text-muted: #94a3b8;
        }

        body {
            background-color: #020617;
            background-image: radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.1) 0px, transparent 50%);
            color: var(--text-main);
            font-family: 'Inter', system-ui, sans-serif;
            font-size: 0.9rem;
            /* Base font size reduced */
            overflow-y: scroll;
            /* Always Show scrollbar to prevent shift */
        }

        /* --- LAYOUT UTILS --- */
        .glass-panel {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
        }

        /* --- CARD DESIGN --- */
        .suite-card {
            display: flex;
            flex-direction: column;
            border-radius: 8px;
            height: 100%;
            max-height: 550px;
            /* FIXED HEIGHT CONSTRAINT */
            overflow: hidden;
            transition: transform 0.2s, border-color 0.2s;
        }

        .suite-card:hover {
            border-color: rgba(59, 130, 246, 0.5);
            transform: translateY(-2px);
        }

        .suite-header {
            padding: 12px 16px;
            background: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .suite-title {
            font-weight: 600;
            font-size: 0.95rem;
            color: #fff;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .suite-body {
            flex-grow: 1;
            overflow-y: auto;
            /* Internal Scroll */
            padding: 8px;
        }

        /* --- LIST ITEMS --- */
        .test-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            margin-bottom: 4px;
            border-radius: 6px;
            background: transparent;
            border: 1px solid transparent;
            transition: background 0.15s;
        }

        .test-item:hover {
            background: rgba(59, 130, 246, 0.1);
            border-color: rgba(59, 130, 246, 0.2);
        }

        .test-name {
            font-family: 'Consolas', monospace;
            font-size: 0.85rem;
            color: var(--text-main);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 220px;
        }

        .btn-run-mini {
            width: 28px;
            height: 28px;
            border-radius: 4px;
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-run-mini:hover {
            background: var(--primary);
            color: #fff;
        }

        /* --- SCROLLBAR --- */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        /* --- CONSOLE DRAWER --- */
        #terminal-drawer {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 600px;
            height: 400px;
            background: #000;
            border: 1px solid #333;
            border-radius: 8px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.8);
            display: none;
            /* Initially Hidden */
            flex-direction: column;
            z-index: 9999;
            resize: both;
            overflow: hidden;
        }

        #terminal-drag-handle {
            background: #1e1e1e;
            padding: 8px 12px;
            cursor: grab;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #333;
            user-select: none;
        }

        #terminal-drag-handle:active {
            cursor: grabbing;
        }

        .term-btn {
            background: transparent;
            border: none;
            color: #ccc;
            width: 24px;
            height: 24px;
            border-radius: 4px;
            cursor: pointer;
        }

        .term-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .term-btn-close:hover {
            background: #ef4444;
        }

        #terminal-content {
            flex-grow: 1;
            padding: 10px;
            font-family: 'Consolas', monospace;
            font-size: 0.8rem;
            color: #00ff00;
            overflow-y: auto;
            white-space: pre-wrap;
        }
    </style>
</head>

<body class="pb-5">

    <!-- HEADER -->
    <header
        class="py-3 px-4 border-bottom border-secondary border-opacity-25 bg-dark d-flex justify-content-between align-items-center sticky-top shadow-sm">
        <div class="d-flex align-items-center gap-3">
            <i class="fa-solid fa-microchip text-primary fs-4"></i>
            <div>
                <h6 class="mb-0 fw-bold text-white uppercase tracking-wider">TOOLKIT ENGINE</h6>
                <div class="small text-muted" style="font-size: 0.75rem;">Compact Grid Edition •
                    <?php echo $totalTestsCount; ?> Tests</div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.toggleTerminal()"
                class="btn btn-sm btn-dark border-secondary border-opacity-25 text-light px-3">
                <i class="fa-solid fa-terminal me-2 text-info"></i>Console
            </button>
            <a href="/fratellanza-militare-archivio/public/devtools" class="btn btn-sm btn-outline-warning px-3" title="Developer Tools">
                <i class="fa-solid fa-toolbox me-2"></i>DevTools
            </a>
            <a href="/fratellanza-militare-archivio/public/impostazioni" class="btn btn-sm btn-outline-light px-3" title="Impostazioni Sistema">
                <i class="fa-solid fa-cog me-2"></i>Settings
            </a>
            <a href="/fratellanza-militare-archivio/public/" class="btn btn-sm btn-primary px-3" title="Torna alla Dashboard">
                <i class="fa-solid fa-home me-2"></i>Home
            </a>
        </div>
    </header>

    <!-- MAIN GRID CONTAINER -->
    <div class="container-fluid px-4 py-4">

        <div class="row g-3"> <!-- G-3 = Compact Gap -->

            <!-- --- AUTOMATION SCRIPTS CARD --- -->
            <?php if (!empty($binScripts)): ?>
                <div class="col-12 col-md-6 col-lg-4 col-xl-4">
                    <div class="suite-card glass-panel" style="border-top: 3px solid #f59e0b;"> <!-- Amber -->
                        <div class="suite-header">
                            <span class="suite-title text-warning"><i class="fa-solid fa-bolt me-2"></i>Automation
                                Scripts</span>
                            <span class="badge bg-white bg-opacity-10 text-white"><?php echo count($binScripts); ?></span>
                        </div>
                        <div class="suite-body custom-scrollbar">
                            <?php foreach ($binScripts as $script): ?>
                                <div class="test-item">
                                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                                        <span class="badge bg-dark border border-secondary border-opacity-25 text-muted"
                                            style="font-size:0.65rem; width: 40px; text-align:center;"><?php echo $script['type']; ?></span>
                                        <div class="test-name" title="<?php echo $script['name']; ?>">
                                            <?php echo $script['name']; ?></div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-muted"
                                            style="font-size: 0.7rem;"><?php echo $script['last_mod']; ?></span>
                                        <button class="btn-run-mini" onclick="runTest('<?php echo $script['rel_path']; ?>')"
                                            title="Run">
                                            <i class="fa-solid fa-play fa-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- --- TEST SUITES LOOP --- -->
            <?php foreach ($grouped as $cat => $tests): ?>
                <div class="col-12 col-md-6 col-lg-4 col-xl-4">
                    <div class="suite-card glass-panel" style="border-top: 3px solid #3b82f6;"> <!-- Blue -->
                        <div class="suite-header">
                            <span class="suite-title text-primary"><i
                                    class="fa-solid fa-folder-open me-2 opacity-50"></i><?php echo $cat; ?></span>
                            <span class="badge bg-white bg-opacity-10 text-white"><?php echo count($tests); ?></span>
                        </div>
                        <div class="suite-body custom-scrollbar">
                            <?php foreach ($tests as $t): ?>
                                <div class="test-item">
                                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                                        <i class="fa-regular fa-file-code text-muted opacity-50" style="font-size: 0.8rem;"></i>
                                        <div class="test-name" title="<?php echo $t['name']; ?>"><?php echo $t['name']; ?></div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-dark text-muted border border-secondary border-opacity-10"
                                            style="font-size: 0.65rem; min-width:25px;"><?php echo $t['count']; ?></span>
                                        <button class="btn-run-mini" onclick="runTest('<?php echo $t['rel_path']; ?>')"
                                            title="Run Test">
                                            <i class="fa-solid fa-flask fa-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        </div> <!-- End Row -->
    </div>

    <!-- CONSOLE DRAWER -->
    <div id="terminal-drawer">
        <div id="terminal-drag-handle">
            <span class="fw-bold small text-light"><i class="fa-solid fa-terminal me-2"></i>SYSTEM CONSOLE</span>
            <div>
                <button class="term-btn" onclick="clearLog()" title="Clear"><i class="fa-solid fa-eraser"></i></button>
                <button class="term-btn term-btn-close" onclick="window.toggleTerminal()" title="Close"><i
                        class="fa-solid fa-times"></i></button>
            </div>
        </div>
        <div id="terminal-content">
            <div class="text-muted opacity-50">// Console initialized. Ready for output.</div>
        </div>
        <div class="d-flex align-items-center p-2 border-top border-secondary border-opacity-25 bg-black">
             <span class="text-success fw-bold me-2 font-monospace" style="font-size: 0.8rem;" id="term-prompt">PS ></span>
             <input type="text" id="term-input" class="bg-transparent border-0 text-white w-100 font-monospace" style="outline:none; font-size: 0.8rem;" autocomplete="off" placeholder="Type command...">
        </div>
    </div>

    <!-- OUTPUT MODAL (Fullscreen Overlay for Detailed Results) -->
    <div id="outputModal"
        style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.9); z-index:10000; padding:40px;">
        <div class="h-100 w-100 bg-dark border border-secondary rounded shadow-lg d-flex flex-column overflow-hidden">
            <div class="p-3 border-bottom border-secondary d-flex justify-content-between align-items-center bg-black">
                <h5 class="m-0 text-white font-monospace">EXECUTION RESULT</h5>
                <button onclick="closeModal()" class="btn btn-danger btn-sm px-4">CLOSE</button>
            </div>
            <div id="termOutput" class="p-3 flex-grow-1 overflow-auto font-monospace text-light"
                style="font-size:0.9rem; white-space:pre-wrap;"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // --- CONSOLE & DRAGGING ---
        document.addEventListener('DOMContentLoaded', () => {
            const drawer = document.getElementById('terminal-drawer');
            const handle = document.getElementById('terminal-drag-handle');
            /* Robust Drag Logic */
            let isDragging = false;
            let offset = { x: 0, y: 0 };

            handle.addEventListener('mousedown', (e) => {
                // Ignore button clicks inside handle
                if (e.target.closest('button')) return;
                isDragging = true;
                offset.x = e.clientX - drawer.offsetLeft;
                offset.y = e.clientY - drawer.offsetTop;
                handle.style.cursor = 'grabbing';
            });

            document.addEventListener('mousemove', (e) => {
                if (!isDragging) return;
                e.preventDefault();
                drawer.style.left = (e.clientX - offset.x) + 'px';
                drawer.style.top = (e.clientY - offset.y) + 'px';
            });

            document.addEventListener('mouseup', () => {
                isDragging = false;
                handle.style.cursor = 'grab';
            });
            
            // --- INTERACTIVE SHELL LOGIC ---
            const termInput = document.getElementById('term-input');
            const termContent = document.getElementById('terminal-content');
            
            termInput.addEventListener('keydown', (e) => {
                if(e.key === 'Enter') {
                    const cmd = termInput.value.trim();
                    if(!cmd) return;
                    
                    // Echo command
                    termContent.innerHTML += `<div class="text-white opacity-75 border-top border-secondary border-opacity-10 mt-1 pt-1"><span class="text-success me-2">PS ></span>${cmd}</div>`;
                    termContent.scrollTop = termContent.scrollHeight;
                    termInput.value = '';

                    // Execute
                    fetch('/fratellanza-militare-archivio/bin/debug_tools/terminal.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({cmd: cmd})
                    })
                    .then(r => r.json())
                    .then(data => {
                        if(data.output === '__CLEAR__') {
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
            log(`Starting: ${relPath}...`, 'info');

            // Show Modal Loader
            const modal = document.getElementById('outputModal');
            const out = document.getElementById('termOutput');
            modal.style.display = 'block';
            out.innerHTML = '<div class="text-center mt-5"><i class="fa-solid fa-spinner fa-spin fs-1 text-primary"></i><br>Executing...</div>';

            fetch('/fratellanza-militare-archivio/bin/debug_tools/run_test.php?file=' + encodeURIComponent(relPath))
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
            if (!confirm("Eseguire TUTTE le suite? Potrebbe richiedere tempo.")) return;
            log(">>> Avvio esecuzione massiva...", 'warning');
            // Logic to iterate buttons would go here.
            alert("Mass execution logic placeholder.");
        }
    </script>
</body>

</html>