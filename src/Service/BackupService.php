<?php

namespace FratellanzaMilitare\Service;

use Psr\Log\LoggerInterface;

class BackupService
{
    private string $dbPath;
    private string $backupDir;
    private LoggerInterface $logger;
    private int $retentionDays;

    public function __construct(string $dbPath, string $backupDir, LoggerInterface $logger, int $retentionDays = 7)
    {
        $this->dbPath = $dbPath;
        $this->backupDir = $backupDir;
        $this->logger = $logger;
        $this->retentionDays = $retentionDays;

        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0777, true);
        }
    }

    /**
     * Esegue il backup del database SQLite
     */
    public function executeBackup(): bool
    {
        $timestamp = date('Ymd_His');
        $backupFile = $this->backupDir . '/database_backup_' . $timestamp . '.sqlite';

        try {
            // Per SQLite, essendo un singolo file, possiamo copiarlo se non ci sono scritture in corso,
            // oppure usare il comando .backup via CLI se disponibile.
            // Una copia sicura in PHP richiede che il file non sia bloccato.
            // Alternativa: usare PDO per eseguire un comando VACUUM INTO se supportato.

            if (!copy($this->dbPath, $backupFile)) {
                throw new \Exception("Impossibile copiare il database in $backupFile");
            }

            $this->logger->info("Backup database completato con successo", ['file' => $backupFile]);

            $this->rotateBackups();

            return true;
        } catch (\Exception $e) {
            $this->logger->error("Errore durante il backup del database: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Rimuove i backup più vecchi della retention impostata
     */
    private function rotateBackups(): void
    {
        $files = glob($this->backupDir . '/database_backup_*.sqlite');
        $now = time();

        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= $this->retentionDays * 86400) {
                    unlink($file);
                    $this->logger->info("Backup rimosso per rotazione (retention: {$this->retentionDays} giorni)", ['file' => $file]);
                }
            }
        }
    }
}
