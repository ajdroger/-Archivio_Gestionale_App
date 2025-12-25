<?php
/**
 * Fratellanza Militare - Test & Automation Dashboard
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Funzione per mappare le cartelle dei test
function getTestFiles($dir)
{
    if (!is_dir($dir)) {
        return [];
    }
    $it = new RecursiveDirectoryIterator($dir);
    $display = [];
    foreach (new RecursiveIteratorIterator($it) as $file) {
        if ($file->getExtension() === 'php' && strpos($file->getFilename(), 'Test') !== false) {
            $path = str_replace('\\', '/', $file->getPathname());
            $display[] = [
                'name' => $file->getFilename(),
                'path' => $path,
                'category' => basename(dirname($path))
            ];
        }
    }
    return $display;
}

// Funzione per mappare gli script in bin
function getBinScripts($dir)
{
    if (!is_dir($dir)) {
        return [];
    }
    $files = glob($dir . '/*.{php,ps1}', GLOB_BRACE);
    return array_map(function ($f) {
        return [
            'name' => basename($f),
            'type' => pathinfo($f, PATHINFO_EXTENSION),
            'last_mod' => date('Y-m-d H:i', filemtime($f))
        ];
    }, $files);
}

$testFiles = getTestFiles(__DIR__);
$binScripts = getBinScripts(__DIR__ . '/../bin');

?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>Test Dashboard - Fratellanza Militare</title>
    <link rel="stylesheet" href="/fratellanza-militare-archivio/public/css/premium.css">
    <script src="/fratellanza-militare-archivio/public/script/app.js" defer></script>
</head>

<body>
    <div class="container">
        <header>
            <h1>🛡️ Fratellanza Militare - Enterprise Quality Dashboard (v2.0)</h1>
            <a href="../Debug/debug_dashboard.php" class="btn" style="background: #94a3b8;">Torna al Debug</a>
        </header>

        <div class="grid">
            <!-- Test Suites -->
            <div class="card">
                <h2>🔍 Test Suites Disponibili</h2>
                <div class="stats">
                    <div class="stat-box"><span class="stat-val"><?php echo count($testFiles); ?></span><span
                            class="stat-label">File Test</span></div>
                    <div class="stat-box" style="border-left: 4px solid #7f1d1d;"><span
                            class="stat-val">GDPR</span><span class="stat-label">Compliant</span></div>
                </div>
                <div style="margin-top: 20px;">
                    <?php foreach ($testFiles as $test): ?>
                        <div class="list-item">
                            <span><?php echo $test['name']; ?></span>
                            <span
                                class="badge badge-<?php echo strtolower($test['category']); ?>"><?php echo $test['category']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Automation Scripts -->
            <div class="card">
                <h2>⚡ Script di Automazione (bin/)</h2>
                <div style="margin-top: 10px;">
                    <?php foreach ($binScripts as $script): ?>
                        <div class="list-item">
                            <div>
                                <strong><?php echo $script['name']; ?></strong><br>
                                <small style="color: #64748b;">Modificato: <?php echo $script['last_mod']; ?></small>
                            </div>
                            <span
                                class="badge badge-<?php echo $script['type']; ?>"><?php echo strtoupper($script['type']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div
                    style="margin-top: 24px; padding: 15px; background: #0f172a; border-radius: 8px; border-left: 4px solid #38bdf8;">
                    <p style="margin: 0; font-size: 0.85rem; color: #94a3b8;">
                        💡 <strong>Tip:</strong> Per eseguire la verifica completa, usa il comando PowerShell:<br>
                        <code style="color: #38bdf8;">.\bin\full_check.ps1</code>
                    </p>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top: 30px; text-align: center;">
            <h2>🛠️ Azioni Rapide</h2>
            <div style="display: flex; justify-content: center; gap: 20px; margin-top: 15px;">
                <a href="xdebug_test.php" class="btn">Test Xdebug UI</a>
                <a href="../bin/check_system.php" class="btn" style="background: #818cf8;">Esegui Check Rapido</a>
                <a href="../bin/simulation.php" class="btn" style="background: #a855f7;">Lancia Simulazione</a>
            </div>
        </div>
    </div>
</body>

</html>
