<?php
/**
 * MCAG System - Test & Automation Dashboard (Compact Grid Edition)
 * @version 5.4.5
 */

require_once __DIR__ . '/../../vendor/autoload.php';

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

    $projectRoot = realpath(__DIR__ . '/../../');

    foreach (new RecursiveIteratorIterator($it) as $file) {
        if ($file->getExtension() === 'php' && strpos($file->getFilename(), 'Test') !== false) {
            $path = str_replace('\\', '/', $file->getRealPath());

            // Skip archived or legacy directories
            if (str_contains($path, '/Archived/') || str_contains($path, '/Archive/')) {
                continue;
            }

            // Calculate relative path from project root
            $relPathFromRoot = str_replace(str_replace('\\', '/', $projectRoot) . '/', '', $path);

            $category = basename(dirname($path));
            if ($category == 'tests')
                $category = 'Root Suite';

            $testCount = countTestsInFile($path);
            $totalTests += $testCount;

            $display[] = [
                'name' => $file->getFilename(),
                'rel_path' => $relPathFromRoot,
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
    $files = array_merge(
        glob($dir . '/*.{php,ps1}', GLOB_BRACE),
        glob($dir . '/debug_tools/*.{php,ps1}', GLOB_BRACE),
        glob($dir . '/restored/*.{php,ps1}', GLOB_BRACE)
    );
    return array_map(function ($f) use ($dir) {
        $rel = str_replace(dirname($dir) . '/', '', $f); // bin/foo.php
        // Fix backslashes for Windows consistency
        $rel = str_replace('\\', '/', $rel);
        // Ensure bin/ prefix if missing (relative path fix)
        if (!str_starts_with($rel, 'bin/'))
            $rel = 'bin/' . basename($f);
        // Better: just use realpath relative to project root usually
        // But for now, let's stick to simple relative format expected by run_test.php
        $rel = str_replace(realpath($dir . '/..') . DIRECTORY_SEPARATOR, '', realpath($f));
        $rel = str_replace('\\', '/', $rel);

        return [
            'name' => basename($f),
            'rel_path' => $rel,
            'type' => pathinfo($f, PATHINFO_EXTENSION),
            'last_mod' => date('d/m H:i', filemtime($f)) // Compact date
        ];
    }, $files);
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
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>Toolkit Compact - MCAG System</title>
    <link rel="stylesheet" href="../../public/css/premium.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../public/css/all.min.css">

    <link rel="stylesheet" href="../../public/css/components/toolkit.css?v=<?php echo time(); ?>">
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
                    <?php echo $totalTestsCount; ?> Tests
                </div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.toggleTerminal()"
                class="btn btn-sm btn-dark border-secondary border-opacity-25 text-light px-3">
                <i class="fa-solid fa-terminal me-2 text-info"></i>Console
            </button>
            <a href="../../public/devtools" class="btn btn-sm btn-outline-warning px-3" title="Developer Tools">
                <i class="fa-solid fa-toolbox me-2"></i>DevTools
            </a>
            <button class="btn btn-sm btn-warning shadow-sm border-0 px-3 fw-bold d-flex align-items-center gap-2"
                data-bs-toggle="modal" data-bs-target="#modal-settings" title="Configurazione Toolkit">
                <i class="fa-solid fa-gears"></i> CONFIGURA <span id="header-status-badge"
                    class="badge bg-white bg-opacity-20 text-white rounded-pill"
                    style="font-size: 0.65rem; display: none;">0</span>
            </button>
            <?php
            $baseUrl = (function () {
                $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
                // Se siamo in bin/debug_tools, dobbiamo risalire di 2 livelli per arrivare alla root, 
                // poi aggiungere /public se necessario (assumendo che il router sia in public/index.php)
                // Ma in questo progetto, public/ è la cartella dove vive il router.
                $rootPath = str_replace('/bin/debug_tools', '', $scriptDir);
                return rtrim($rootPath, '/') . '/public';
            })();
            ?>
            <a href="<?php echo $baseUrl; ?>/impostazioni" class="btn btn-sm btn-outline-light px-3"
                title="Impostazioni Sistema">
                <i class="fa-solid fa-cog me-2"></i>Impostazioni
            </a>
            <a href="<?php echo $baseUrl; ?>/" class="btn btn-sm btn-primary px-3" title="Torna alla Dashboard">
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
                            <button class="btn btn-sm btn-outline-warning ms-auto border-0" onclick="runAll()"
                                title="Esegui Safe Runner (Tutti i Test)"><i class="fa-solid fa-play-circle fa-lg"></i> Run
                                All</button>
                        </div>
                        <div class="suite-body custom-scrollbar">
                            <?php foreach ($binScripts as $script): ?>
                                <div class="test-item">
                                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                                        <span class="badge bg-dark border border-secondary border-opacity-25 text-muted"
                                            style="font-size:0.65rem; width: 40px; text-align:center;"><?php echo $script['type']; ?></span>
                                        <div class="test-name" title="<?php echo $script['name']; ?>">
                                            <?php echo $script['name']; ?>
                                        </div>
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
            <span class="text-success fw-bold me-2 font-monospace" style="font-size: 0.8rem;" id="term-prompt">PS
                ></span>
            <input type="text" id="term-input" class="bg-transparent border-0 text-white w-100 font-monospace"
                style="outline:none; font-size: 0.8rem;" autocomplete="off" placeholder="Type command...">
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

    <!-- SETTINGS MODAL (Redesigned) -->
    <div class="modal fade" id="modal-settings" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div
                class="modal-content bg-dark border border-secondary border-opacity-50 glass-panel shadow-2xl overflow-hidden">
                <!-- Header con gradiente -->
                <div
                    class="modal-header bg-gradient-to-r from-blue-900 to-transparent border-bottom border-secondary border-opacity-25 py-3">
                    <h5 class="modal-title text-white fw-bold d-flex align-items-center">
                        <div class="bg-primary p-2 rounded me-3 shadow-blue-glow"><i
                                class="fa-solid fa-sliders text-white"></i></div>
                        CONFIGURAZIONE TOOLKIT
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Gruppo: Parametri Esecuzione -->
                    <div class="mb-4">
                        <label class="text-secondary small fw-bold uppercase-tracking mb-3 d-block"><i
                                class="fa-solid fa-terminal me-2"></i>PARAMETRI DI ESECUZIONE</label>

                        <div class="card bg-black bg-opacity-30 border-secondary border-opacity-25 p-3 mb-2 hover-bg-primary-fade transition-all cursor-pointer"
                            onclick="document.getElementById('setting-verbose').click()">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white mb-0 small fw-bold">Modalità Verbosa</h6>
                                    <p class="text-muted mb-0" style="font-size: 0.75rem;">Mostra log dettagliati e
                                        output completi dei test.</p>
                                </div>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" id="setting-verbose">
                                </div>
                            </div>
                        </div>

                        <div class="card bg-black bg-opacity-30 border-secondary border-opacity-25 p-3 hover-bg-danger-fade transition-all cursor-pointer"
                            onclick="document.getElementById('setting-stop-failure').click()">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white mb-0 small fw-bold">Stop On Failure</h6>
                                    <p class="text-muted mb-0" style="font-size: 0.75rem;">Interrompe la suite al primo
                                        errore riscontrato.</p>
                                </div>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" id="setting-stop-failure">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gruppo: Interfaccia -->
                    <div class="mb-4">
                        <label class="text-secondary small fw-bold uppercase-tracking mb-3 d-block"><i
                                class="fa-solid fa-desktop me-2"></i>PREFERENZE INTERFACCIA</label>

                        <div class="card bg-black bg-opacity-30 border-secondary border-opacity-25 p-3 hover-bg-primary-fade transition-all cursor-pointer"
                            onclick="document.getElementById('setting-auto-clear').click()">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white mb-0 small fw-bold">Auto-Pulisci Console</h6>
                                    <p class="text-muted mb-0" style="font-size: 0.75rem;">Svuota il terminale prima di
                                        ogni nuova esecuzione.</p>
                                </div>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" id="setting-auto-clear" checked>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="alert alert-warning bg-warning bg-opacity-10 border-warning border-opacity-25 text-warning small mb-0 px-3 py-2 d-flex align-items-center">
                        <i class="fa-solid fa-floppy-disk me-3 fs-5"></i>
                        <span>Le preferenze vengono salvate nel browser per le prossime sessioni.</span>
                    </div>
                </div>

                <div class="modal-footer border-top border-secondary border-opacity-25 p-3">
                    <button type="button" class="btn btn-primary w-100 fw-bold py-2 shadow-sm" data-bs-dismiss="modal">
                        <i class="fa-solid fa-check-circle me-2"></i>APPLICA CONFIGURAZIONE
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../../public/js/lib/bootstrap.bundle.min.js"></script>
    <script src="../../public/js/components/toolkit.js?v=<?php echo time(); ?>"></script>
</body>

</html>