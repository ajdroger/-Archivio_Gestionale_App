<?php

namespace MCAG\Debug;

use PDO;
use Psr\Log\LoggerInterface;

class ResilienceMonitor
{
    private PDO $pdo;
    private LoggerInterface $logger;
    private string $storagePath;

    public function __construct(PDO $pdo, LoggerInterface $logger, string $storagePath)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
        $this->storagePath = $storagePath;
    }

    /**
     * Esegue un check completo della resilienza
     */
    public function monitorHealth(): array
    {
        $this->logger->debug("Avvio monitoraggio salute resilienza...");
        return [
            'database' => $this->checkDatabaseIntegrity(),
            'backups' => $this->checkBackupState(),
            'logs' => $this->checkLogTracciability(),
            'security' => $this->checkSecurityConstraints()
        ];
    }

    private function checkDatabaseIntegrity(): array
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $stmt = $this->pdo->query("PRAGMA integrity_check");
            $result = $stmt->fetchColumn();

            $foreignKeyCheck = $this->pdo->query("PRAGMA foreign_key_check");
            $foreignKeys = $foreignKeyCheck ? $foreignKeyCheck->fetchAll() : [];

            return [
                'status' => ($result === 'ok' && empty($foreignKeys)),
                'integrity' => $result,
                'foreign_key_violations' => count($foreignKeys)
            ];
        } elseif ($driver === 'mysql') {
            $tables = ['users', 'soci', 'documenti', 'audit_logs'];
            $details = [];
            $allOk = true;

            foreach ($tables as $table) {
                try {
                    $stmt = $this->pdo->query("CHECK TABLE `$table`");
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (($row['Msg_text'] ?? '') !== 'OK') {
                        $allOk = false;
                        $details[$table] = $row['Msg_text'] ?? 'Unknown Error';
                    }
                } catch (\Exception $e) {
                    $allOk = false;
                    $details[$table] = $e->getMessage();
                }
            }

            return [
                'status' => $allOk,
                'integrity' => $allOk ? 'ok' : 'errors detected',
                'details' => $details,
                'foreign_key_violations' => 0 // Not easily queryable globally in MySQL
            ];
        }

        return [
            'status' => true,
            'integrity' => 'driver not supported',
            'foreign_key_violations' => 0
        ];
    }

    private function checkBackupState(): array
    {
        $backupDir = $this->storagePath . '/backups';
        $backups = glob($backupDir . '/database_backup_*.sqlite');

        if (empty($backups)) {
            return ['status' => false, 'message' => 'Nessun backup trovato'];
        }

        usort($backups, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $latestAge = (time() - filemtime($backups[0])) / 3600;

        return [
            'status' => $latestAge < 24,
            'count' => count($backups),
            'latest_age_hours' => round($latestAge, 1)
        ];
    }

    private function checkLogTracciability(): array
    {
        $logFile = __DIR__ . '/../../var/logs/app.log';
        if (!file_exists($logFile)) {
            return ['status' => false, 'message' => 'Log file mancante'];
        }

        // Memory-safe check: Read only last 10KB
        $foundId = false;
        $fp = @fopen($logFile, 'rb');
        if ($fp) {
            fseek($fp, -10240, SEEK_END); // Go back 10KB (or start if smaller)
            if (ftell($fp) < 0)
                rewind($fp); // Reset if file is smaller than 10KB

            $chunk = fread($fp, 10240);
            fclose($fp);

            if ($chunk && strpos($chunk, 'request_id') !== false) {
                $foundId = true;
            }
        }

        return [
            'status' => $foundId,
            'message' => $foundId ? 'Correlation IDs presenti' : 'ATTENZIONE: Log senza Request ID'
        ];
    }

    private function checkSecurityConstraints(): array
    {
        $httpsOn = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

        return [
            'session_secure' => ini_get('session.cookie_secure') === "1" || !$httpsOn,
            'session_httponly' => ini_get('session.cookie_httponly') === "1",
            'session_samesite' => strtolower((string) ini_get('session.cookie_samesite')) === 'strict'
        ];
    }
}


