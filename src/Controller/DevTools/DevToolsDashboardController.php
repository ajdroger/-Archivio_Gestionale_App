<?php

namespace FratellanzaMilitare\Controller\DevTools;

use Mustache_Engine;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;
use FratellanzaMilitare\SecurityLayer\AuditTrail;
use FratellanzaMilitare\Debug\ResilienceMonitor;
use FratellanzaMilitare\Debug\SessionInspector;

/**
 * DevTools Dashboard Controller
 * 
 * Handles main dashboard and overview functionality
 */
class DevToolsDashboardController
{
    private Mustache_Engine $mustache;
    private ResilienceMonitor $monitor;

    public function __construct(Mustache_Engine $mustache, ResilienceMonitor $monitor)
    {
        $this->mustache = $mustache;
        $this->monitor = $monitor;
    }

    public function dashboard(Request $request, Response $response): Response
    {
        // Monitor system health including disk space, database connectivity, and PHP extensions
        $health = $this->monitor->monitorHealth();
        $params = $request->getQueryParams();

        // --- Audit Logs Retrieval ---
        // Filters are applied to the AuditTrail via the Repository-like `ricercaAzioni` method.
        // This supports filtering by Date Range, Username, and Resource ID.
        $auditFilters = [];
        if (!empty($params['start_date'])) {
            $auditFilters['start_date'] = $params['start_date'];
        }
        if (!empty($params['end_date'])) {
            $auditFilters['end_date'] = $params['end_date'];
        }
        if (!empty($params['audit_user'])) {
            $auditFilters['username'] = $params['audit_user'];
        }
        if (!empty($params['resource_id'])) {
            $auditFilters['resource_id'] = $params['resource_id'];
        }

        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $perPage = 20;

        $db = DatabaseConnection::getConnection();
        $auditTrail = AuditTrail::getInstance();
        $auditTrail->setPdo($db); // Ensure PDO injection for the Singleton

        $auditResult = $auditTrail->ricercaAzioni($auditFilters, $page, $perPage);
        $auditLogs = $auditResult['data'];
        $pagination = [
            'total' => $auditResult['total'],
            'per_page' => $auditResult['per_page'],
            'current_page' => $auditResult['current_page'],
            'last_page' => $auditResult['last_page'],
            'has_prev' => $page > 1,
            'has_next' => $page < $auditResult['last_page'],
            'prev_page' => $page - 1,
            'next_page' => $page + 1
        ];

        // System Diagnostic Information
        // Collected to provide immediate visibility into the runtime environment.
        // Crucial for debugging "It works on my machine" issues.
        $systemInfo = [
            'php_version' => phpversion(),
            'os' => php_uname(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'db_driver' => $db->getAttribute(\PDO::ATTR_DRIVER_NAME),
            'app_version' => '2.0 Mission-Critical Enterprise', // UPDATED VERSION
            'memory_limit' => ini_get('memory_limit'),
            'upload_max' => ini_get('upload_max_filesize'),
            'post_max' => ini_get('post_max_size'),
            'max_execution' => ini_get('max_execution_time') . 's'
        ];

        // Database Schema Stats
        $schemaStats = $this->getSchemaStats($db);

        // Scan for Scripts
        $scripts = $this->scanScripts();

        // Log check
        $logs = $this->getRecentLogs();

        $html = $this->mustache->render('devtools', [
            'title' => 'Dashboard Sviluppatore',
            'system' => $systemInfo,
            'health' => $health,
            'logs' => $logs,
            'audit_logs' => $auditLogs,
            'database_schema' => $schemaStats,
            'filters' => $params,
            'scripts' => $scripts,
            'is_admin' => ($_SESSION['user_role'] ?? '') === 'admin',
            'username' => $_SESSION['username'] ?? 'Utente',
            'user_initial' => strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)),
            'session_debug' => (function () {
                $d = SessionInspector::inspect();
                $kv = [];
                if (isset($d['data']) && is_array($d['data'])) {
                    foreach ($d['data'] as $k => $v) {
                        $kv[] = ['key' => $k, 'val' => is_string($v) ? $v : json_encode($v)];
                    }
                    $d['data_kv'] = $kv;
                }
                return $d;
            })()
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    private function getSchemaStats(\PDO $db): array
    {
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

    private function scanScripts(): array
    {
        $baseDir = __DIR__ . '/../../../';

        return [
            'tests_integration' => $this->scanDir($baseDir . 'tests/Integration'),
            'tests_legacy' => $this->scanDir($baseDir . 'tests/Legacy'),
            'maintenance' => $this->scanDir($baseDir . 'bin/maintenance'),
            'setup' => $this->scanDir($baseDir . 'bin/setup'),
            'tools' => $this->scanDir($baseDir . 'bin/tools'),
            'debug_tools' => $this->scanDir($baseDir . 'bin/debug_tools'),
            'src_debug' => $this->scanDir($baseDir . 'src/Debug', ['GlobalExceptionHandler.php', 'DatabaseInspector.php', 'LogViewer.php', 'QueryLogger.php', 'UserErrorLogger.php', 'SystemCheck.php'])
        ];
    }

    private function getRecentLogs(): array
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

    private function scanDir(string $dir, array $exclude = []): array
    {
        $files = [];
        if (!is_dir($dir)) {
            return [];
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filename = $file->getFilename();
                if (in_array($filename, $exclude) || str_starts_with($filename, '.')) {
                    continue;
                }

                $relativePath = str_replace(realpath(__DIR__ . '/../../../') . DIRECTORY_SEPARATOR, '', $file->getRealPath());
                $relativePath = str_replace('\\', '/', $relativePath);

                $desc = 'Script PHP';
                if (str_contains($relativePath, 'Feature')) {
                    $desc = 'Test Funzionale';
                }
                if (str_contains($relativePath, 'Unit')) {
                    $desc = 'Test Unitario';
                }
                if (str_ends_with($filename, '.ps1')) {
                    $desc = 'Script PowerShell';
                }

                $files[] = [
                    'name' => $filename,
                    'path' => $relativePath,
                    'desc' => $desc
                ];
            }
        }
        return $files;
    }
}
