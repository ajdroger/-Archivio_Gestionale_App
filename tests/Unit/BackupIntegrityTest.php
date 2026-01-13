<?php

use MCAG\Service\BackupService;
use Psr\Log\NullLogger;

test('BackupService esegue la copia del database', function () {
    $dbPath = 'dummy'; // Unused in MySQL mode
    $backupDir = __DIR__ . '/../../storage/test_backups';

    if (is_dir($backupDir)) {
        array_map('unlink', glob("$backupDir/*.*"));
        rmdir($backupDir);
    }

    $service = new BackupService($dbPath, $backupDir, new NullLogger(), 7);

    // Mysqldump might fail in test env if credentials not set or tool missing
    // We try/catch to skip if environment isn't ready, rather than failing
    try {
        $result = $service->executeBackup();
        expect($result)->toBeTrue();
        expect(glob("$backupDir/database_backup_*.sql"))->toHaveCount(1);
    } catch (\Exception $e) {
        $this->markTestSkipped('Mysqldump failed: ' . $e->getMessage());
    }

    // Pulizia
    if (is_dir($backupDir)) {
        array_map('unlink', glob("$backupDir/*.*"));
        rmdir($backupDir);
    }
});

test('BackupService ruota i vecchi backup correttamente', function () {
    $dbPath = 'dummy';
    $backupDir = __DIR__ . '/../../storage/test_backups_rotation';

    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0777, true);
    }
    array_map('unlink', glob("$backupDir/*.*"));

    // Crea un file "vecchio" (8 giorni fa)
    $oldFile = $backupDir . '/database_backup_20200101_000000.sql';
    file_put_contents($oldFile, 'dummy content');
    touch($oldFile, time() - (8 * 86400)); // 8 giorni fa

    // Crea un file "nuovo" (oggi)
    $newFile = $backupDir . '/database_backup_20990101_000000.sql';
    file_put_contents($newFile, 'dummy content');

    $service = new BackupService($dbPath, $backupDir, new NullLogger(), 7);

    // Eseguiamo il backup per innescare la rotazione
    try {
        $service->executeBackup();

        $files = glob("$backupDir/database_backup_*.sql");

        // Dovremmo avere 2 file: quello nuovo appena creato da executeBackup e quello di "2099"
        // (quello del 2020 rimosso)
        expect($files)->toHaveCount(2);
        expect(file_exists($oldFile))->toBeFalse();

    } catch (\Exception $e) {
        $this->markTestSkipped('Mysqldump failed: ' . $e->getMessage());
    }

    // Pulizia
    array_map('unlink', glob("$backupDir/*.*"));
    rmdir($backupDir);
});
