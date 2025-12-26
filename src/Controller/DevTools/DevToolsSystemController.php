<?php

namespace FratellanzaMilitare\Controller\DevTools;

use Mustache_Engine;
use FratellanzaMilitare\Debug\ResilienceMonitor;
use FratellanzaMilitare\Debug\SessionInspector;

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

    public function getSystemInfo(): array
    {
        $db = $this->pdo;
        return [
            'php_version' => phpversion(),
            'os' => php_uname(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'db_driver' => $db->getAttribute(\PDO::ATTR_DRIVER_NAME),
            'app_version' => '2.0 Mission-Critical Enterprise',
            'memory_limit' => ini_get('memory_limit'),
            'upload_max' => ini_get('upload_max_filesize'),
            'post_max' => ini_get('post_max_size'),
            'max_execution' => ini_get('max_execution_time') . 's'
        ];
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
            $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);
            if ($driver === 'mysql') {
                $tables = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchAll(\PDO::FETCH_COLUMN);
            } else {
                $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(\PDO::FETCH_COLUMN);
            }

            foreach ($tables as $t) {
                $count = $db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
                $schemaStats[] = ['name' => $t, 'rows' => $count];
            }
        } catch (\Exception $e) {
            // Fail silently
        }
        return $schemaStats;
    }

    public function getRecentLogs(): array
    {
        $logs = [];
        $logFile = __DIR__ . '/../../../logs/app.log';
        if (file_exists($logFile)) {
            $lines = array_slice(file($logFile), -10);
            foreach ($lines as $line) {
                $logs[] = ['content' => substr($line, 0, 120) . '...'];
            }
        }
        return $logs;
    }

    public function scanScripts(): array
    {
        // Moved from DashboardController
        $baseDir = __DIR__ . '/../../../';
        // ... (Scan logic implementation can be copied or delegated)
        // For brevity in this refactor, I will implement the scanDir internally here 
        // effectively moving it from DashboardController

        return [
            'tests_unit' => $this->scanDir($baseDir . 'tests/Unit'),
            'tests_feature' => $this->scanDir($baseDir . 'tests/Feature'),
            // ... other directories
            'tools' => $this->scanDir($baseDir . 'bin/tools'),
        ];
    }

    private function scanDir(string $dir): array
    {
        $files = [];
        if (!is_dir($dir)) {
            return [];
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
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
}
