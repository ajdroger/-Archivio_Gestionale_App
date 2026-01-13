<?php

namespace MCAG\Service;

use Psr\Log\LoggerInterface;

/**
 * Servizio per la gestione dei backup del database.
 * 
 * Si occupa di eseguire il dump del database MySQL utilizzando mysqldump,
 * gestire la rotazione dei file vecchi e loggare l'esito delle operazioni.
 */
/**
 * Servizio per la gestione dei backup del database.
 * 
 * Si occupa di eseguire il dump del database MySQL utilizzando mysqldump,
 * gestire la rotazione dei file vecchi e loggare l'esito delle operazioni.
 */
class BackupService
{
    private string $backupDir;
    private LoggerInterface $logger;
    private int $retentionDays;

    // dbPath unused for MySQL, kept for signature compatibility if needed but ignored
    public function __construct(string $dbPath, string $backupDir, LoggerInterface $logger, int $retentionDays = 7)
    {
        $this->backupDir = $backupDir;
        $this->logger = $logger;
        $this->retentionDays = $retentionDays;

        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0777, true);
        }
    }

    /**
     * Esegue il backup del database MySQL.
     * 
     * Rileva automaticamente il percorso di mysqldump (su Windows cerca percorsi comuni).
     * Recupera le credenziali dall'ambiente (.env).
     * Gestisce la rotazione automatica post-backup.
     * 
     * @return bool True se il backup è riuscito, False altrimenti.
     */
    /**
     * Esegue il backup del database MySQL.
     * 
     * Rileva automaticamente il percorso di mysqldump (su Windows cerca percorsi comuni).
     * Recupera le credenziali dall'ambiente (.env).
     * Gestisce la rotazione automatica post-backup.
     * 
     * @return bool True se il backup è riuscito, False altrimenti.
     */
    public function executeBackup(): bool
    {
        $timestamp = date('Ymd_His');
        $backupFile = $this->backupDir . '/database_backup_' . $timestamp . '.sql';

        try {
            $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $user = $_ENV['DB_USERNAME'] ?? 'root';
            // Important: Handle empty password. If empty, don't pass -p
            $pass = $_ENV['DB_PASSWORD'] ?? '';
            $db = $_ENV['DB_DATABASE'] ?? 'fratellanza_db';

            // Detect mysqldump path
            $mysqldump = $_ENV['MYSQLDUMP_PATH'] ?? 'mysqldump';
            if ($mysqldump === 'mysqldump' && stripos(PHP_OS, 'WIN') === 0) {
                $candidates = [
                    'C:/Program Files/Ampps/mysql/bin/mysqldump.exe',
                    'C:/xampp/mysql/bin/mysqldump.exe'
                ];
                foreach ($candidates as $candidate) {
                    if (file_exists($candidate)) {
                        $mysqldump = '"' . $candidate . '"'; // Quote path
                        break;
                    }
                }
            }

            // Command Construction
            $passPart = empty($pass) ? '' : "--password=\"$pass\"";
            $cmd = "$mysqldump --host=$host --user=$user $passPart $db > \"$backupFile\" 2>&1";

            // Execute
            $output = null;
            $resultCode = null;
            exec($cmd, $output, $resultCode);

            if ($resultCode !== 0) {
                // Check if mysqldump is missing
                throw new \Exception("Backup failed (Code $resultCode). Check if 'mysqldump' is in your PATH. Output: " . implode("\n", $output));
            }

            $this->logger->info("Backup MySQL completato con successo", ['file' => $backupFile]);

            $this->rotateBackups();

            return true;
        } catch (\Exception $e) {
            $this->logger->error("Errore durante il backup MySQL: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Rimuove i backup più vecchi della retention impostata.
     * 
     * I file vengono mantenuti per il numero di giorni specificato nel costruttore.
     */
    /**
     * Rimuove i backup più vecchi della retention impostata.
     * 
     * I file vengono mantenuti per il numero di giorni specificato nel costruttore.
     */
    private function rotateBackups(): void
    {
        $files = glob($this->backupDir . '/database_backup_*.sql');
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


