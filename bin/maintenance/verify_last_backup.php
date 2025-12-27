#!/usr/bin/env php
<?php

/**
 * Script di verifica ultimo backup
 * 
 * Usage: php bin/maintenance/verify_last_backup.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;
use FratellanzaMilitare\Service\BackupVerificationService;
use Dotenv\Dotenv;

// Load environment
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

echo "🔍 Verifica Ultimo Backup\n";
echo "=========================\n\n";

// Initialize service
$pdo = DatabaseConnection::getConnection();
$verificationService = new BackupVerificationService($pdo);

// Verify
$result = $verificationService->verifyLatestBackup();

if ($result['success']) {
    echo "✅ Backup verificato con successo!\n\n";
    echo "File: {$result['backup_file']}\n";
    echo "Dimensione: {$result['file_size']}\n";
    echo "Durata verifica: {$result['duration']}\n\n";

    echo "Integrity Checks:\n";
    foreach ($result['checks'] as $check => $value) {
        $displayValue = is_numeric($value) ? $value : $value;
        echo "  • $check: $displayValue\n";
    }

    exit(0);
} else {
    echo "❌ Verifica backup fallita!\n\n";
    echo "Errore: {$result['message']}\n";
    exit(1);
}
