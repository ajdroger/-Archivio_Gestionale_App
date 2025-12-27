<?php

require __DIR__ . '/../../vendor/autoload.php';

use DI\ContainerBuilder;
use FratellanzaMilitare\Service\BackupService;

// 1. Inizializza il container
$containerBuilder = new ContainerBuilder();
foreach ((require __DIR__ . '/../../config/container.php') as $definitions) {
    $containerBuilder->addDefinitions($definitions);
}
$container = $containerBuilder->build();

// 1.5 Correlation ID for CLI
if (!isset($_SERVER['HTTP_X_REQUEST_ID'])) {
    $_SERVER['HTTP_X_REQUEST_ID'] = 'CLI-' . bin2hex(random_bytes(4));
}

// 2. Ottieni il servizio di backup
try {
    /** @var BackupService $backupService */
    $backupService = $container->get(BackupService::class);

    echo "Avvio backup del database...\n";

    if ($backupService->executeBackup()) {
        echo "[OK] Backup completato con successo.\n";
        exit(0);
    } else {
        echo "[ERRORE] Il backup è fallito. Controlla i log.\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "[CRITICO] Errore durante l'inizializzazione del backup: " . $e->getMessage() . "\n";
    exit(1);
}
