<?php

declare(strict_types=1);

namespace MCAG\Service;

use PDO;
use Exception;

/**
 * Backup Verification Service
 * 
 * Verifica l'integrità dei backup MySQL tramite restore su database temporaneo.
 * Esegue integrity checks e query di test per assicurarsi che i backup siano recuperabili.
 */
class BackupVerificationService
{
    private PDO $pdo;
    private string $backupDir;

    public function __construct(PDO $pdo, string $backupDir = 'storage/backups')
    {
        $this->pdo = $pdo;
        $this->backupDir = $backupDir;
    }

    /**
     * Verifica l'ultimo backup disponibile
     */
    public function verifyLatestBackup(): array
    {
        $latestBackup = $this->findLatestBackup();

        if (!$latestBackup) {
            return [
                'success' => false,
                'message' => 'Nessun backup trovato',
            ];
        }

        return $this->verifyBackup($latestBackup);
    }

    /**
     * Verifica un backup specifico
     */
    public function verifyBackup(string $backupFile): array
    {
        $startTime = microtime(true);
        $tempDbName = 'fm_backup_verify_' . time();

        try {
            // 1. Check file existence and readability
            if (!file_exists($backupFile)) {
                throw new Exception("Backup file not found: $backupFile");
            }

            if (!is_readable($backupFile)) {
                throw new Exception("Backup file not readable: $backupFile");
            }

            $fileSize = filesize($backupFile);
            if ($fileSize === 0) {
                throw new Exception("Backup file is empty");
            }

            // 2. Create temporary database
            $this->createTemporaryDatabase($tempDbName);

            // 3. Restore backup to temp database
            $this->restoreBackup($backupFile, $tempDbName);

            // 4. Run integrity checks
            $checks = $this->runIntegrityChecks($tempDbName);

            // 5. Cleanup temporary database
            $this->dropTemporaryDatabase($tempDbName);

            $duration = round(microtime(true) - $startTime, 2);

            return [
                'success' => true,
                'message' => 'Backup verified successfully',
                'backup_file' => basename($backupFile),
                'file_size' => $this->formatBytes($fileSize),
                'duration' => $duration . 's',
                'checks' => $checks,
            ];

        } catch (Exception $e) {
            // Cleanup on error
            try {
                $this->dropTemporaryDatabase($tempDbName);
            } catch (Exception $cleanup) {
                // Ignore cleanup errors
            }

            return [
                'success' => false,
                'message' => 'Backup verification failed: ' . $e->getMessage(),
                'backup_file' => basename($backupFile),
            ];
        }
    }

    /**
     * Trova l'ultimo backup disponibile
     */
    private function findLatestBackup(): ?string
    {
        $backups = glob($this->backupDir . '/backup_*.sql');

        if (empty($backups)) {
            return null;
        }

        // Sort by modification time descending
        usort($backups, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        return $backups[0];
    }

    /**
     * Crea database temporaneo
     */
    private function createTemporaryDatabase(string $dbName): void
    {
        $this->pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName`");
    }

    /**
     * Restore backup su database temporaneo
     */
    private function restoreBackup(string $backupFile, string $dbName): void
    {
        // Read SQL file
        $sql = file_get_contents($backupFile);

        if ($sql === false) {
            throw new Exception("Cannot read backup file");
        }

        // Use temporary database
        $this->pdo->exec("USE `$dbName`");

        // Execute SQL statements
        // Split by semicolon and execute each statement
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($stmt) => !empty($stmt) && !str_starts_with($stmt, '--')
        );

        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $this->pdo->exec($statement);
            }
        }

        // Switch back to original database
        $dbName = getenv('DB_DATABASE');
        $this->pdo->exec("USE `$dbName`");
    }

    /**
     * Esegue integrity checks sul database restored
     */
    private function runIntegrityChecks(string $dbName): array
    {
        $this->pdo->exec("USE `$dbName`");

        $checks = [];

        // Check 1: Table count
        $stmt = $this->pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $checks['table_count'] = count($tables);

        // Check 2: Required tables exist
        $requiredTables = ['soci', 'utenti', 'documenti', 'audit_log'];
        $missingTables = array_diff($requiredTables, $tables);
        $checks['required_tables'] = empty($missingTables) ? 'OK' : 'MISSING: ' . implode(', ', $missingTables);

        // Check 3: Row counts
        foreach (['soci', 'utenti', 'documenti'] as $table) {
            if (in_array($table, $tables)) {
                $stmt = $this->pdo->query("SELECT COUNT(*) FROM `$table`");
                $checks["rows_$table"] = $stmt->fetchColumn();
            }
        }

        // Check 4: Data integrity - sample queries
        if (in_array('soci', $tables)) {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM soci WHERE codice_fiscale IS NOT NULL");
            $checks['soci_with_cf'] = $stmt->fetchColumn();
        }

        // Switch back to original database
        $originalDbName = getenv('DB_DATABASE');
        $this->pdo->exec("USE `$originalDbName`");

        return $checks;
    }

    /**
     * Elimina database temporaneo
     */
    private function dropTemporaryDatabase(string $dbName): void
    {
        $this->pdo->exec("DROP DATABASE IF EXISTS `$dbName`");
    }

    /**
     * Format bytes to human-readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}


