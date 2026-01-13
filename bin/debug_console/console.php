<?php

require __DIR__ . '/../../vendor/autoload.php';

use DI\ContainerBuilder;
use MCAG\Debug\ResilienceMonitor;
use MCAG\Debug\LogAnalyzer;
use MCAG\Service\BackupService;

// Inizializza il container
$containerBuilder = new ContainerBuilder();
foreach ((require __DIR__ . '/../../config/container.php') as $definitions) {
    $containerBuilder->addDefinitions($definitions);
}
$container = $containerBuilder->build();

echo "================================================\n";
echo "   APP_VER: 2.0 Mission-Critical Enterprise\n";
echo "================================================\n";

function printUsage()
{
    echo "\nComandi disponibili:\n";
    echo "  health   - Esegue il monitoraggio proattivo della resilienza\n";
    echo "  backup   - Esegue un nuovo backup del database\n";
    echo "  logs     - Mostra un riepilogo degli ultimi log (errori/warning)\n";
    echo "  trace ID - Estrae i log correlati a un Request ID specifico\n";
    echo "  exit     - Esci dalla console\n\n";
}

printUsage();

while (true) {
    echo "mc-console> ";
    $input = trim(fgets(STDIN));
    $parts = explode(' ', $input);
    $command = $parts[0] ?? '';

    switch ($command) {
        case 'health':
            $monitor = $container->get(ResilienceMonitor::class);
            $health = $monitor->monitorHealth();
            echo "\n--- SYSTEM HEALTH REPORT ---\n";
            foreach ($health as $comp => $data) {
                $status = ($data['status'] ?? false) ? "[OK]" : "[FAIL]";
                echo "$status " . strtoupper($comp) . ": " . ($data['message'] ?? 'Check completato') . "\n";
                if ($comp === 'database') {
                    echo "      Integro: " . ($data['integrity'] == 'ok' ? 'SI' : 'NO') . "\n";
                    echo "      Violazioni FK: " . $data['foreign_key_violations'] . "\n";
                }
            }
            break;

        case 'backup':
            $backup = $container->get(BackupService::class);
            if ($backup->executeBackup()) {
                echo "[OK] Backup creato con successo.\n";
            } else {
                echo "[ERR] Errore durante la creazione del backup.\n";
            }
            break;

        case 'logs':
            $analyzer = new LogAnalyzer(__DIR__ . '/../../logs/app.log');
            $summary = $analyzer->getSummary(50);
            echo "\n--- LOG SUMMARY (Last 50 entries) ---\n";
            echo "Total lines: {$summary['total']}\n";
            echo "Errors:      {$summary['errors']}\n";
            echo "Warnings:    {$summary['warnings']}\n";
            echo "Top Requests (Correlation IDs):\n";
            arsort($summary['requests']);
            foreach (array_slice($summary['requests'], 0, 5) as $id => $count) {
                echo "  $id: $count eventi\n";
            }
            break;

        case 'trace':
            $id = $parts[1] ?? '';
            if (empty($id)) {
                echo "[ERR] Specificare un Request ID (trace <ID>)\n";
                break;
            }
            $analyzer = new LogAnalyzer(__DIR__ . '/../../logs/app.log');
            $logs = $analyzer->getLogsByRequestId($id);
            echo "\n--- TRACE FOR REQUEST ID: $id ---\n";
            foreach ($logs as $log) {
                echo sprintf("[%s] [%s] %s\n", $log['datetime'], $log['level_name'], $log['message']);
            }
            if (empty($logs))
                echo "Nessun log trovato per questo ID.\n";
            break;

        case 'exit':
            echo "Chiusura console...\n";
            exit(0);

        default:
            if (!empty($command))
                echo "Comando non riconosciuto: $command\n";
            printUsage();
            break;
    }
}

