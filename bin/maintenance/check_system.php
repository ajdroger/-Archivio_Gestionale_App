<?php

require __DIR__ . '/../../vendor/autoload.php';

// Registra la gestione globale degli errori con un logger temporaneo per CLI
$cliLogger = new \Monolog\Logger('cli-system-check');
$cliLogger->pushHandler(new \Monolog\Handler\StreamHandler(__DIR__ . '/../logs/app.log', \Monolog\Logger::DEBUG));
\FratellanzaMilitare\Debug\GlobalExceptionHandler::registerGlobalHandlers($cliLogger);

use FratellanzaMilitare\Debug\SystemCheck;

// Configurazione
$logFile = __DIR__ . '/../logs/controllo_sistema.log';
$routesToCheck = [
    '/' => 'FratellanzaMilitare\Controller\HomeController:dashboard',
    '/soci' => 'FratellanzaMilitare\Controller\SocioController:list',
    '/soci/{cf}' => 'FratellanzaMilitare\Controller\SocioController:detail'
];

$checker = new SystemCheck();
$timestamp = date('Y-m-d H:i:s');
$outputBuffer = "";

// Intestazione
$header = "=== REPORT CONTROLLO SISTEMA [$timestamp] ===\n";
echo $header;
$outputBuffer .= $header;

// 1. Diagnostica Sistema Generiche
echo "\n--- Diagnostica Infrastruttura ---\n";
$outputBuffer .= "\n--- Diagnostica Infrastruttura ---\n";

$sysResults = $checker->runDiagnostics();
$sysFailures = 0;

foreach ($sysResults as $key => $check) {
    // If it's a nested array (like 'extensions'), we might need to iterate or just show a summary
    if ($key === 'extensions') {
        foreach ($check as $extName => $extCheck) {
            printDiagnosticLine($extName, $extCheck, $sysFailures);
        }
        continue;
    }

    if ($key === 'filesystem' && isset($check['details'])) {
        printDiagnosticLine('Filesystem', $check, $sysFailures);
        foreach ($check['details'] as $subKey => $subCheck) {
            printDiagnosticLine("  -> $subKey", $subCheck, $sysFailures);
        }
        continue;
    }

    printDiagnosticLine($key, $check, $sysFailures);
}

function printDiagnosticLine($key, $check, &$failures)
{
    global $outputBuffer;
    $status = $check['status'] ?? true;
    $statusIcon = $status ? "[OK]" : "[FAIL]";
    $message = $check['message'] ?? $key;
    $line = sprintf("%-10s %s\n", $statusIcon, $message);

    echo $line;
    $outputBuffer .= $line;

    if (!$status) {
        $failures++;
    }
}

// 2. Controllo Integrità Rotte
echo "\n--- Controllo Integrità Rotte ---\n";
$outputBuffer .= "\n--- Controllo Integrità Rotte ---\n";

$routeResults = $checker->checkRoutes($routesToCheck);
$routeFailures = 0;

foreach ($routeResults as $route => $check) {
    $statusIcon = $check['status'] ? "[OK]" : "[FAIL]";
    $line = sprintf("%-10s %s\n", $statusIcon, $check['message']);

    echo $line;
    $outputBuffer .= $line;

    if (!$check['status']) {
        $routeFailures++;
        $cliLogger->error("Fallimento controllo rotta: {$route}", ['details' => $check['message']]);
    }
}

// Riepilogo
$totalFailures = $sysFailures + $routeFailures;
$footer = "\n=== RIEPILOGO ===\n";
$footer .= "Errori Sistema: $sysFailures\n";
$footer .= "Errori Rotte:   $routeFailures\n";
$footer .= "STATO TOTALE:   " . ($totalFailures === 0 ? "NESSUN ERRORE RILEVATO" : "$totalFailures ERRORI RILEVATI") . "\n";
$footer .= "=================================================\n\n";

echo $footer;
$outputBuffer .= $footer;

// Scrittura Log
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0777, true);
}
file_put_contents($logFile, $outputBuffer, FILE_APPEND);

echo "Log salvato in: $logFile\n";
