<?php

namespace MCAG\Controller\DevTools;

use Mustache_Engine;
use MCAG\Debug\ResilienceMonitor;
use MCAG\Debug\SessionInspector;

/**
 * Controller per la gestione e il monitoraggio del sistema.
 * 
 * Raccoglie metriche vitali dal server (CPU, RAM, Disco),
 * dal database (schema stats), da Redis e da Git.
 * Fornisce dati per la dashboard e widget di monitoraggio.
 */
class DevToolsSystemController
{
    private Mustache_Engine $mustache;
    private ResilienceMonitor $monitor;
    private \PDO $pdo;

    public function __construct(Mustache_Engine $mustache, ResilienceMonitor $monitor, \PDO $pdo)
    {
        $this->mustache = $mustache;
        $this->monitor = $monitor;
        $this->pdo = $pdo;
    }

    /**
     * Recupera informazioni generali sul sistema.
     * 
     * Include versione PHP, OS, Driver DB, limiti di memoria e upload,
     * e stato dettagliato di OPCache se attivo.
     * 
     * @return array
     */
    public function getSystemInfo(): array
    {
        $db = $this->pdo;

        // OPCache Granular
        $opcacheStatus = function_exists('opcache_get_status') ? @opcache_get_status(false) : null;
        $opcacheMem = $opcacheStatus['memory_usage'] ?? null;

        return [
            'php_version' => phpversion(),
            'os' => php_uname(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'db_driver' => $db->getAttribute(\PDO::ATTR_DRIVER_NAME),
            'app_version' => '3.2 Expert', // Updated version
            'memory_limit' => ini_get('memory_limit'),
            'memory_usage_real' => $this->formatBytes(memory_get_usage(true)),
            'upload_max' => ini_get('upload_max_filesize'),
            'post_max' => ini_get('post_max_size'),
            'max_execution' => ini_get('max_execution_time') . 's',
            'disk_free' => $this->safeDiskSpace('free'),
            'disk_total' => $this->safeDiskSpace('total'),
            'disk_percent' => $this->safeDiskPercent(),
            'opcache_enabled' => $opcacheStatus && ($opcacheStatus['opcache_enabled'] ?? false),
            'opcache_stats' => $opcacheMem ? [
                'used' => $this->formatBytes($opcacheMem['used_memory']),
                'free' => $this->formatBytes($opcacheMem['free_memory']),
                'wasted' => $this->formatBytes($opcacheMem['wasted_memory']),
                'hit_rate' => round($opcacheStatus['opcache_statistics']['opcache_hit_rate'] ?? 0, 1),
                'percent_used' => round(($opcacheMem['used_memory'] / ($opcacheMem['used_memory'] + $opcacheMem['free_memory'])) * 100, 1)
            ] : null,
            'error_count' => $this->countRecentErrors()
        ];
    }

    /**
     * Recupera statistiche da Redis (se disponibile).
     * 
     * Tenta la connessione al server Redis configurato e recupera info
     * su memoria, client connessi e chiavi.
     * 
     * @return array
     */
    public function getRedisStats(): array
    {
        $status = 'offline';
        $info = [
            'version' => 'N/A',
            'uptime' => '-',
            'used_memory' => '0B',
            'connected_clients' => 0,
            'total_connections' => 0,
            'key_count' => 0
        ];

        try {
            // Quick check if Predis is available (it is in composer)
            if (class_exists('Predis\Client')) {
                // Hardcoded localhost for now, or fetch from ENVs
                $client = new \Predis\Client([
                    'scheme' => 'tcp',
                    'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
                    'port' => $_ENV['REDIS_PORT'] ?? 6379,
                    'read_write_timeout' => 1 // Fast timeout
                ]);
                $client->connect();
                if ($client->isConnected()) {
                    $status = 'online';
                    $rawInfo = $client->info();
                    $info = [
                        'version' => $rawInfo['Server']['redis_version'] ?? '?',
                        'uptime' => ($rawInfo['Server']['uptime_in_days'] ?? 0) . 'd',
                        'used_memory' => $rawInfo['Memory']['used_memory_human'] ?? '0B',
                        'connected_clients' => $rawInfo['Clients']['connected_clients'] ?? 0,
                        'total_connections' => $rawInfo['Stats']['total_connections_received'] ?? 0,
                        'key_count' => $rawInfo['Keyspace']['db0']['keys'] ?? 0 // Assuming db0
                    ];
                }
            }
        } catch (\Exception $e) {
            $status = 'error';
        }

        return ['status' => $status, 'details' => $info];
    }

    /**
     * Recupera informazioni sullo stato del repository Git.
     * 
     * Esegue comandi git per ottenere branch, ultimo commit e stato 'dirty' (modifiche non committate).
     * 
     * @return array
     */
    public function getGitInfo(): array
    {
        $baseDir = __DIR__ . '/../../../';
        if (!is_dir($baseDir . '.git')) {
            return ['active' => false];
        }

        try {
            $branch = trim(shell_exec("cd $baseDir && git rev-parse --abbrev-ref HEAD"));
            $commit = trim(shell_exec("cd $baseDir && git log -1 --format=\"%h|%s|%an|%ar\""));
            $statusRaw = shell_exec("cd $baseDir && git status --porcelain");
            $isDirty = !empty(trim($statusRaw));

            $commits = explode('|', $commit);

            return [
                'active' => true,
                'branch' => $branch,
                'short_hash' => $commits[0] ?? '????',
                'message' => $commits[1] ?? '',
                'author' => $commits[2] ?? '',
                'time' => $commits[3] ?? '',
                'dirty' => $isDirty
            ];
        } catch (\Exception $e) {
            return ['active' => false];
        }
    }

    public function getPrivacyStats(): array
    {
        // 1. Log Redaction (Approximate)
        $logFile = __DIR__ . '/../../../var/logs/app.log';
        $maskedLogs = 0;
        $maskedLogEntries = [];

        if (file_exists($logFile)) {
            $lastLines = $this->tailFile($logFile, 200);
            foreach ($lastLines as $l) {
                if (str_contains($l, '*****') || str_contains($l, '[REDACTED]')) {
                    $maskedLogs++;
                    // Extract timestamp and brief context
                    preg_match('/^\[(.*?)\]/', $l, $matches);
                    $ts = $matches[1] ?? 'N/A';
                    $maskedLogEntries[] = ['timestamp' => $ts, 'snippet' => substr(strip_tags($l), 0, 50) . '...'];
                }
            }
        }

        // 2. Encrypted Secrets (Users with 2FA)
        $encryptedUsers = [];
        $encryptedSecrets = 0;
        try {
            $stmt = $this->pdo->query("SELECT username, id FROM users WHERE totp_secret IS NOT NULL AND LENGTH(totp_secret) > 32");
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $encryptedSecrets = count($rows);
            foreach ($rows as $r) {
                $encryptedUsers[] = ['username' => $r['username'], 'id' => $r['id']];
            }
        } catch (\Exception $e) {
            $encryptedSecrets = 0;
        }

        return [
            'masked_logs_count' => $maskedLogs,
            'encrypted_secrets' => $encryptedSecrets,
            'anonymized_records' => 0,
            'details' => [
                'users' => $encryptedUsers,
                'logs' => $maskedLogEntries
            ]
        ];
    }

    private function safeDiskSpace($type)
    {
        try {
            $bytes = $type === 'free' ? @disk_free_space('.') : @disk_total_space('.');
            return $bytes !== false ? $this->formatBytes($bytes) : 'N/A';
        } catch (\Throwable $e) {
            return 'N/A';
        }
    }

    private function safeDiskPercent()
    {
        try {
            $free = @disk_free_space('.');
            $total = @disk_total_space('.');
            if ($total === false || $total <= 0)
                return 0;
            return round((1 - ($free / $total)) * 100, 1);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function getHealth(): array
    {
        return $this->monitor->monitorHealth();
    }

    public function getSessionDebug(): array
    {
        $d = SessionInspector::inspect();
        $kv = [];
        if (isset($d['data']) && is_array($d['data'])) {
            foreach ($d['data'] as $k => $v) {
                $kv[] = ['key' => $k, 'val' => is_string($v) ? $v : json_encode($v)];
            }
            $d['data_kv'] = $kv;
        }
        return $d;
    }

    public function getSchemaStats(): array
    {
        $db = $this->pdo;
        $schemaStats = [];
        try {
            $tables = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($tables as $t) {
                // Get precise rows (COUNT*) and data size
                $stats = $db->query("SHOW TABLE STATUS LIKE '$t'")->fetch(\PDO::FETCH_ASSOC);
                $rowCount = $db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();

                $sizeBytes = ($stats['Data_length'] ?? 0) + ($stats['Index_length'] ?? 0);

                $schemaStats[] = [
                    'name' => $t,
                    'rows' => $rowCount,
                    'size_bytes' => $sizeBytes,
                    'size_formatted' => $this->formatBytes($sizeBytes)
                ];
            }
        } catch (\Exception $e) {
            // Fail silently
        }
        return $schemaStats;
    }

    public function getRecentLogs(): array
    {
        $logs = [];
        $logFile = __DIR__ . '/../../../var/logs/app.log';
        if (file_exists($logFile)) {
            $lines = $this->tailFile($logFile, 20); // Last 20 lines
            foreach ($lines as $line) {
                if (trim($line)) {
                    $logs[] = ['content' => substr($line, 0, 160) . (strlen($line) > 160 ? '...' : '')];
                }
            }
        }
        return $logs;
    }

    public function scanScripts(): array
    {
        $baseDir = __DIR__ . '/../../../';

        return [
            'maintenance' => $this->scanDir($baseDir . 'bin/maintenance'),
            'setup' => $this->scanDir($baseDir . 'bin/setup'),
            'tools' => $this->scanDir($baseDir . 'bin/tools'),
            'debug_tools' => $this->scanDir($baseDir . 'bin/debug_tools'),
            'utilities' => $this->scanDir($baseDir . 'bin/tools'), // General tools moved here
        ];
    }

    private function scanDir(string $dir, bool $recursive = true): array
    {
        $files = [];
        if (!is_dir($dir)) {
            return [];
        }

        if ($recursive) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        } else {
            $iterator = new \DirectoryIterator($dir);
        }

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filename = $file->getFilename();
                if (str_starts_with($filename, '.'))
                    continue;

                $relativePath = str_replace(realpath(__DIR__ . '/../../../') . DIRECTORY_SEPARATOR, '', $file->getRealPath());
                $relativePath = str_replace('\\', '/', $relativePath);

                $files[] = [
                    'name' => $filename,
                    'path' => $relativePath,
                    'desc' => 'Script File'
                ];
            }
        }
        return $files;
    }
    public function getActiveSessionsCount(): int
    {
        $sessionPath = session_save_path();
        if (empty($sessionPath)) {
            $sessionPath = sys_get_temp_dir();
        }
        // Count files initiating with sess_
        $files = glob($sessionPath . '/sess_*');
        // Filter by age (e.g. last 30 mins) if needed, but raw count is faster for now
        // Let's filter slightly to check only valid files
        return $files ? count($files) : 0;
    }

    public function getLatencyMetrics(): array
    {
        $start = microtime(true);
        try {
            $this->pdo->query('SELECT 1');
            $dbLatency = round((microtime(true) - $start) * 1000); // ms
        } catch (\Exception $e) {
            $dbLatency = 999;
        }
        return [
            'db_ms' => $dbLatency,
            'status' => $dbLatency < 50 ? 'excellent' : ($dbLatency < 200 ? 'good' : 'slow')
        ];
    }

    public function getIntrusionStats(): array
    {
        $logFile = __DIR__ . '/../../../var/logs/app.log';
        if (!file_exists($logFile)) {
            return ['count' => 0, 'status' => 'clean'];
        }

        // Optimized read: Read last 50KB instead of whole file
        $content = $this->tailFile($logFile, 100);
        $count = 0;
        foreach ($content as $line) {
            if (stripos($line, 'Login failed') !== false || stripos($line, 'Unauthorized') !== false || stripos($line, 'suspicious') !== false) {
                $count++;
            }
        }
        return [
            'count' => $count,
            'status' => $count === 0 ? 'clean' : ($count < 5 ? 'warning' : 'danger')
        ];
    }

    private function countRecentErrors(): int
    {
        $logFile = __DIR__ . '/../../../var/logs/app.log';
        if (!file_exists($logFile))
            return 0;

        $lines = $this->tailFile($logFile, 100);
        $count = 0;
        foreach ($lines as $line) {
            if (stripos($line, '.ERROR') !== false || stripos($line, 'CRITICAL') !== false) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Efficiently read the last N lines of a file using fseek
     */
    private function tailFile(string $file, int $lines): array
    {
        $f = @fopen($file, "rb");
        if ($f === false)
            return [];

        fseek($f, -1, SEEK_END);
        if (ftell($f) < 2)
            return []; // Empty or small

        $chunkSize = 4096;
        $output = '';
        $readLines = 0;

        while (ftell($f) > 0 && $readLines <= $lines) {
            $seek = min(ftell($f), $chunkSize);
            fseek($f, -$seek, SEEK_CUR);
            $output = ($chunk = fread($f, $seek)) . $output;
            fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
            $readLines = substr_count($output, "\n");
        }

        fclose($f);
        return array_slice(explode("\n", $output), -$lines);
    }

    private function formatBytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = array('', 'KB', 'MB', 'GB', 'TB');
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }

    /**
     * Esegue comandi shell sicuri per il Terminal Emulator.
     * 
     * @param string $cmd Il comando inviato dall'interfaccia
     * @return string Output del comando o errore
     */
    public function executeShellCommand(string $cmd): string
    {
        $cmd = trim($cmd);

        // whitelist di comandi consentiti
        $allowed = ['ls', 'pwd', 'whoami', 'date', 'uptime', 'git', 'tail', 'php -v', 'composer', 'dir'];

        $isAllowed = false;
        foreach ($allowed as $a) {
            if (str_starts_with($cmd, $a)) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            return "Error: Command '{$cmd}' is restricted in this environment for security reasons.";
        }

        // Prevenzione Injection banale
        if (str_contains($cmd, '&&') || str_contains($cmd, ';') || str_contains($cmd, '|')) {
            return "Error: Chained commands are not allowed.";
        }

        $baseDir = realpath(__DIR__ . '/../../../');
        $cwd = getcwd();
        chdir($baseDir); // Esegui context root

        try {
            // Esecuzione
            $output = shell_exec($cmd . " 2>&1");
            return $output ?? "";
        } catch (\Throwable $e) {
            return "Execution Error: " . $e->getMessage();
        } finally {
            chdir($cwd); // Restore
        }
    }
}


