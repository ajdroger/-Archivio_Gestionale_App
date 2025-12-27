<?php
// Endpoint per l'esecuzione dei test via AJAX
require_once __DIR__ . '/../../vendor/autoload.php';

header('Content-Type: text/plain; charset=utf-8');

$file = $_GET['file'] ?? '';

// Basic Security Check
$baseDir = realpath(__DIR__ . '/../../');
$requestedFile = realpath($baseDir . '/' . $file);
$testsDir = realpath($baseDir . '/tests');
$binDir = realpath($baseDir . '/bin');

if (!$requestedFile || (!str_starts_with($requestedFile, $testsDir) && !str_starts_with($requestedFile, $binDir))) {
    http_response_code(403);
    echo "Errore: File non autorizzato (Consentiti solo /tests e /bin).";
    exit;
}

$flags = [];
if (($_GET['verbose'] ?? '') === 'true')
    $flags[] = '--verbose';
if (($_GET['stop-on-failure'] ?? '') === 'true')
    $flags[] = '--stop-on-failure';
$flagsStr = implode(' ', $flags);

$cmd = '';

// Determine Runner Engine
if (str_starts_with($requestedFile, $testsDir)) {
    // Pest for Tests
    $pestScript = $baseDir . '/vendor/bin/pest';

    // Use PHP to run the script directly, avoiding .bat wrapper issues
    // IMPORTANT: cd to baseDir so it finds phpunit.xml and resolves paths correctly
    $cmd = 'cd /d "' . $baseDir . '" && php "' . $pestScript . '" --configuration phpunit.xml --colors=always ' . $flagsStr . ' "' . $requestedFile . '" 2>&1';
} else {
    // Runner for Scripts
    $ext = pathinfo($requestedFile, PATHINFO_EXTENSION);
    if ($ext === 'php') {
        $cmd = 'php "' . $requestedFile . '" ' . $flagsStr . ' 2>&1';
    } elseif ($ext === 'ps1') {
        $cmd = 'powershell -ExecutionPolicy Bypass -File "' . $requestedFile . '" 2>&1';
    } else {
        echo "Estensione file non supportata per l'esecuzione automatica.";
        exit;
    }
}

echo "Esecuzione di: " . basename($requestedFile) . "\n";
echo "Command: $cmd \n";
echo str_repeat('-', 40) . "\n";

// Execute
// Note: This might take time, so set timeout
set_time_limit(60);
$output = shell_exec($cmd);

echo $output;
