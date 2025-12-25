<?php

use FratellanzaMilitare\Service\BackupService;
use Psr\Log\NullLogger;

// Non serve uses() se definito globalmente in Pest.php per la cartella Unit

test('BackupService esegue la copia del database', function () {
    $dbPath = __DIR__ . '/../../database.sqlite';
    $backupDir = __DIR__ . '/../../storage/test_backups';

    // Assicuriamoci che la directory test esista e sia pulita
    if (is_dir($backupDir)) {
        array_map('unlink', glob("$backupDir/*.*"));
        rmdir($backupDir);
    }

    $service = new BackupService($dbPath, $backupDir, new NullLogger(), 7);

    $result = $service->executeBackup();

    expect($result)->toBeTrue();
    expect(glob("$backupDir/database_backup_*.sqlite"))->toHaveCount(1);

    // Pulizia
    array_map('unlink', glob("$backupDir/*.*"));
    rmdir($backupDir);
});

test('BackupService ruota i vecchi backup correttamente', function () {
    $dbPath = __DIR__ . '/../../database.sqlite';
    $backupDir = __DIR__ . '/../../storage/test_backups_rotation';

    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0777, true);
    }
    array_map('unlink', glob("$backupDir/*.*"));

    // Crea un file "vecchio" (8 giorni fa)
    $oldFile = $backupDir . '/database_backup_20200101_000000.sqlite';
    file_put_contents($oldFile, 'dummy content');
    touch($oldFile, time() - (8 * 86400)); // 8 giorni fa

    // Crea un file "nuovo" (oggi)
    $newFile = $backupDir . '/database_backup_20990101_000000.sqlite';
    file_put_contents($newFile, 'dummy content');

    $service = new BackupService($dbPath, $backupDir, new NullLogger(), 7); // 7 giorni retention

    // Eseguiamo il backup per innescare la rotazione
    $service->executeBackup();

    $files = glob("$backupDir/database_backup_*.sqlite");

    // Dovremmo avere 2 file: quello nuovo appena creato da executeBackup e quello di "2099"
    // perché quello vecchio del 2020 dovrebbe essere stato rimosso.
    expect($files)->toHaveCount(2);
    expect(file_exists($oldFile))->toBeFalse();

    // Pulizia
    array_map('unlink', glob("$backupDir/*.*"));
    rmdir($backupDir);
});
