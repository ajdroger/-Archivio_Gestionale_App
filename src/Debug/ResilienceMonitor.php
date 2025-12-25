<?php

namespace FratellanzaMilitare\Debug;

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
        $stmt = $this->pdo->query("PRAGMA integrity_check");
        $result = $stmt->fetchColumn();

        $foreignKeyCheck = $this->pdo->query("PRAGMA foreign_key_check");
        $foreignKeys = $foreignKeyCheck ? $foreignKeyCheck->fetchAll() : [];

        return [
            'status' => ($result === 'ok' && empty($foreignKeys)),
            'integrity' => $result,
            'foreign_key_violations' => count($foreignKeys)
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
        $logFile = __DIR__ . '/../../logs/app.log';
        if (!file_exists($logFile)) {
            return ['status' => false, 'message' => 'Log file mancante'];
        }

        // Legge le ultime 10 righe per verificare la presenza di request_id
        $lines = array_slice(file($logFile), -10);
        $foundId = false;
        foreach ($lines as $line) {
            if (strpos($line, 'request_id') !== false) {
                $foundId = true;
                break;
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
