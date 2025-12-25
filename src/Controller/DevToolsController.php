<?php

namespace FratellanzaMilitare\Controller;

use Mustache_Engine;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DevToolsController
{
    private Mustache_Engine $mustache;
    private \FratellanzaMilitare\Debug\ResilienceMonitor $monitor;

    public function __construct(Mustache_Engine $mustache, \FratellanzaMilitare\Debug\ResilienceMonitor $monitor)
    {
        $this->mustache = $mustache;
        $this->monitor = $monitor;
    }

    public function dashboard(Request $request, Response $response): Response
    {
        $health = $this->monitor->monitorHealth();
        $params = $request->getQueryParams();

        // --- Audit Logs (NEW) ---
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

        $db = \FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection::getConnection();
        $auditTrail = \FratellanzaMilitare\SecurityLayer\AuditTrail::getInstance();
        $auditTrail->setPdo($db);

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

        $systemInfo = [
            'php_version' => phpversion(),
            'os' => php_uname(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'db_driver' => $db->getAttribute(\PDO::ATTR_DRIVER_NAME),
            'app_version' => '1.3.1 Mission-Critical',
            // Extended Metrics
            'memory_limit' => ini_get('memory_limit'),
            'upload_max' => ini_get('upload_max_filesize'),
            'post_max' => ini_get('post_max_size'),
            'max_execution' => ini_get('max_execution_time') . 's'
        ];

        // 0. Database Schema Stats (Unified)
        $schemaStats = [];
        try {
            $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);

            if ($driver === 'mysql') {
                $tables = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchAll(\PDO::FETCH_COLUMN);
            } else {
                $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(\PDO::FETCH_COLUMN);
            }

            foreach ($tables as $t) {
                // Use standard quoting, or simplified since table names are simple
                $count = $db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
                $schemaStats[] = ['name' => $t, 'rows' => $count];
            }
        } catch (\Exception $e) {
            // Fail silently for stats
        }

        // 1. Scan for Scripts
        $baseDir = __DIR__ . '/../../';

        $scripts = [
            'tests_integration' => $this->scanDir($baseDir . 'tests/Integration'),
            'tests_legacy' => $this->scanDir($baseDir . 'tests/Legacy'),
            'maintenance' => $this->scanDir($baseDir . 'bin/maintenance'),
            'setup' => $this->scanDir($baseDir . 'bin/setup'),
            'tools' => $this->scanDir($baseDir . 'bin/tools'),
            'debug_tools' => $this->scanDir($baseDir . 'bin/debug_tools'),
            'src_debug' => $this->scanDir($baseDir . 'src/Debug', ['GlobalExceptionHandler.php', 'DatabaseInspector.php', 'LogViewer.php', 'QueryLogger.php', 'UserErrorLogger.php', 'SystemCheck.php'])
        ];

        // Basic Log Check (Mock)
        $logs = [];
        $logFile = __DIR__ . '/../../logs/app.log';
        if (file_exists($logFile)) {
            $lines = array_slice(file($logFile), -10);
            foreach ($lines as $line) {
                $logs[] = ['content' => substr($line, 0, 120) . '...'];
            }
        }

        // CSRF Tokens


        $html = $this->mustache->render('devtools', [
            'title' => 'Dashboard Sviluppatore',
            'system' => $systemInfo,
            'health' => $health,
            'logs' => $logs,
            'audit_logs' => $auditLogs,
            'database_schema' => $schemaStats, // NEW
            'filters' => $params, // Pass filters to view
            'scripts' => $scripts,


            'is_admin' => ($_SESSION['user_role'] ?? '') === 'admin',
            'username' => $_SESSION['username'] ?? 'Utente',
            'user_initial' => strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)),
            'session_debug' => (function () {
                $d = \FratellanzaMilitare\Debug\SessionInspector::inspect();
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

    public function exportAuditPdf(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();

        $db = \FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection::getConnection();
        $auditTrail = \FratellanzaMilitare\SecurityLayer\AuditTrail::getInstance();
        $auditTrail->setPdo($db);

        $filters = [];
        if (!empty($params['start_date'])) {
            $filters['start_date'] = $params['start_date'];
        }
        if (!empty($params['end_date'])) {
            $filters['end_date'] = $params['end_date'];
        }
        if (!empty($params['audit_user'])) {
            $filters['username'] = $params['audit_user'];
        }
        if (!empty($params['resource_id'])) {
            $filters['resource_id'] = $params['resource_id'];
        }

        // Get ALL data (unlimited)
        $result = $auditTrail->ricercaAzioni($filters, 1, -1);
        $logs = $result['data'];

        $html = $this->mustache->render('report_pdf', [
            'type_audit' => true,
            'logs' => $logs,
            'filters' => $params,
            'year' => date('Y')
        ]);

        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true]);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html);
        $dompdf->render();

        $output = $dompdf->output();
        $response->getBody()->write($output);

        return $response
            ->withHeader('Content-Type', 'application/pdf')
            ->withHeader('Content-Disposition', 'attachment; filename="Audit_Log_FM_' . date('Y-m-d') . '.pdf"');
    }

    public function exportAuditExcel(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();

        $db = \FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection::getConnection();
        $auditTrail = \FratellanzaMilitare\SecurityLayer\AuditTrail::getInstance();
        $auditTrail->setPdo($db);

        $filters = [];
        if (!empty($params['start_date'])) {
            $filters['start_date'] = $params['start_date'];
        }
        if (!empty($params['end_date'])) {
            $filters['end_date'] = $params['end_date'];
        }
        if (!empty($params['audit_user'])) {
            $filters['username'] = $params['audit_user'];
        }
        if (!empty($params['resource_id'])) {
            $filters['resource_id'] = $params['resource_id'];
        }

        // Get ALL data (unlimited)
        $result = $auditTrail->ricercaAzioni($filters, 1, -1);
        $logs = $result['data'];

        $output = fopen('php://memory', 'r+');
        fputs($output, "\xEF\xBB\xBF"); // BOM

        fputcsv($output, ['Timestamp', 'User', 'Action', 'Resource', 'IP']);
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['timestamp'],
                $log['username'],
                $log['action'],
                $log['resource_id'],
                $log['ip_address']
            ]);
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        $response->getBody()->write($csvContent);

        return $response
            ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition', 'attachment; filename="Audit_Log_FM_' . date('Y-m-d') . '.csv"');
    }

    public function logTrace(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $requestId = $data['requestId'] ?? '';

        if (empty($requestId)) {
            $response->getBody()->write(json_encode(['error' => 'ID richiesto.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $analyzer = new \FratellanzaMilitare\Debug\LogAnalyzer(__DIR__ . '/../../logs/app.log');
        $logs = $analyzer->getLogsByRequestId($requestId);

        $response->getBody()->write(json_encode(['logs' => $logs]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function runScript(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $scriptPath = $data['script'] ?? '';

        // Security: Block directory traversal and restrict to allowed folders
        $baseDir = realpath(__DIR__ . '/../../');
        $realPath = realpath($baseDir . '/' . $scriptPath);

        if (!$realPath || !str_starts_with($realPath, $baseDir)) {
            $response->getBody()->write(json_encode(['output' => 'Errore: Percorso non consentito.']));
            return $response->withHeader('Content-Type', 'application/json');
        }

        $command = '';
        if (str_ends_with($realPath, '.php')) {
            // Check if it's a Pest test
            if (str_contains($realPath, 'tests')) {
                // Use absolute paths for both php and pest for maximum reliability, but run from root
                $command = 'php vendor/bin/pest --configuration phpunit.xml "' . $realPath . '" --colors=never';
            } else {
                $command = 'php "' . $realPath . '"';
            }
        } elseif (str_ends_with($realPath, '.ps1')) {
            $command = 'powershell -ExecutionPolicy Bypass -File "' . $realPath . '"';
        }

        if ($command) {
            // Unlock session to allow concurrent requests while script runs
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            $oldCwd = getcwd();
            chdir($baseDir);
            $output = shell_exec($command . ' 2>&1');
            chdir($oldCwd);
        } else {
            $output = "Tipo di file non supportato.";
        }

        $response->getBody()->write(json_encode(['output' => $output ?: 'Nessun output o errore.']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function runRenamer(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $newName = $data['new_name'] ?? '';
        $isDryRun = ($data['dry_run'] ?? '0') === '1';

        if (empty($newName)) {
            $response->getBody()->write(json_encode(['output' => 'Errore: Nome progetto mancante.']));
            return $response->withHeader('Content-Type', 'application/json');
        }

        // Validate slug format
        if (!preg_match('/^[a-z0-9-]+$/', $newName)) {
            $response->getBody()->write(json_encode(['output' => 'Errore: Il nome deve essere in formato slug (solo lettere minuscole, numeri e trattini).']));
            return $response->withHeader('Content-Type', 'application/json');
        }

        // Include the tool dynamically
        $toolPath = __DIR__ . '/../../tests/RenamerTool/SystemRenamer.php';
        if (!file_exists($toolPath)) {
            $response->getBody()->write(json_encode(['output' => 'Errore: Tool SystemRenamer non trovato sui server.']));
            return $response->withHeader('Content-Type', 'application/json');
        }

        require_once $toolPath;

        // Capture output
        ob_start();
        try {
            // Unlock session to prevent hanging
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            $renamer = new \SystemRenamer($newName, $isDryRun);
            $renamer->execute();

        } catch (\Throwable $e) {
            echo "\n[EXCEPTION] " . $e->getMessage();
        }
        $output = ob_get_clean();

        $response->getBody()->write(json_encode(['output' => $output]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function fsList(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $basePath = realpath(__DIR__ . '/../../');
        $requestPath = $data['path'] ?? '/';

        // Sanitize path
        $targetPath = realpath($basePath . '/' . $requestPath);
        if (!$targetPath || !str_starts_with($targetPath, $basePath)) {
            $targetPath = $basePath; // Fallback to root if invalid
        }

        $items = [];
        if (is_dir($targetPath)) {
            $scanned = scandir($targetPath);
            foreach ($scanned as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $fullPath = $targetPath . '/' . $item;
                $isDir = is_dir($fullPath);
                $relPath = str_replace($basePath, '', $fullPath);
                $relPath = str_replace('\\', '/', $relPath); // Normalize

                $items[] = [
                    'name' => $item,
                    'path' => $relPath,
                    'type' => $isDir ? 'dir' : 'file',
                    'ext' => pathinfo($item, PATHINFO_EXTENSION),
                    'size' => $isDir ? '-' : $this->formatBytes(filesize($fullPath))
                ];
            }
        }

        // Sort: Dirs first
        usort($items, fn ($a, $b) => $b['type'] <=> $a['type']);

        $response->getBody()->write(json_encode([
            'current' => str_replace('\\', '/', str_replace($basePath, '', $targetPath)) ?: '/',
            'items' => $items
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function fsRead(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $path = $data['path'] ?? '';
        $basePath = realpath(__DIR__ . '/../../');
        $fullPath = realpath($basePath . '/' . $path);

        if (!$fullPath || !str_starts_with($fullPath, $basePath) || !is_file($fullPath)) {
            $response->getBody()->write(json_encode(['error' => 'File non trovato o accesso negato.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $content = file_get_contents($fullPath);
        $response->getBody()->write(json_encode(['content' => $content]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function fsSave(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $path = $data['path'] ?? '';
        $content = $data['content'] ?? '';
        $basePath = realpath(__DIR__ . '/../../');
        $fullPath = realpath($basePath . '/' . $path);

        if (!$fullPath || !str_starts_with($fullPath, $basePath) || !is_file($fullPath)) {
            $response->getBody()->write(json_encode(['error' => 'File non valido.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        file_put_contents($fullPath, $content);
        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function dbQuery(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $sql = $data['sql'] ?? '';

        try {
            $pdo = \FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection::getConnection();
            $stmt = $pdo->prepare($sql);
            $stmt->execute();

            $results = [];
            // fetching all only works for SELECT, for others rowCount might be useful
            // But execute() returns true on success.
            // Check if it's a SELECT
            if (preg_match('/^\s*SELECT/i', $sql)) {
                $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } else {
                $results = [['message' => 'Query eseguita.', 'rows_affected' => $stmt->rowCount()]];
            }

            $response->getBody()->write(json_encode(['results' => $results]));
        } catch (\PDOException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

    // --- SECURITY & ACCOUNT CONTROL ---

    public function securityList(Request $request, Response $response): Response
    {
        $pdo = \FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection::getConnection();
        $users = $pdo->query("SELECT id, username, role, created_at, totp_secret, 
            CASE WHEN totp_secret IS NOT NULL THEN 1 ELSE 0 END as has_2fa 
            FROM users ORDER BY username ASC")->fetchAll(\PDO::FETCH_ASSOC);

        // Sanitize secrets for UI
        foreach ($users as &$u) {
            unset($u['totp_secret']);
        }

        $response->getBody()->write(json_encode(['users' => $users]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function securityAdd(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $user = trim($data['username'] ?? '');
        $pass = $data['password'] ?? '';
        $role = $data['role'] ?? 'user';

        if (strlen($user) < 3 || strlen($pass) < 6) {
            return $this->jsonError($response, 'Username min 3 chars, Password min 6 chars.');
        }

        $hash = password_hash($pass, PASSWORD_BCRYPT);
        // Generate Unique Secret (Base32 mockup)
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[rand(0, 31)];
        }

        try {
            $pdo = \FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection::getConnection();
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, totp_secret) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user, $hash, $role, $secret]);
            $response->getBody()->write(json_encode(['success' => true]));
        } catch (\PDOException $e) {
            return $this->jsonError($response, 'Username already exists or DB error.');
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function securityReset(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $id = $data['id'] ?? 0;
        $pass = $data['password'] ?? '';

        if (strlen($pass) < 6) {
            return $this->jsonError($response, 'Password min 6 chars.');
        }

        $hash = password_hash($pass, PASSWORD_BCRYPT);

        $pdo = \FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection::getConnection();
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $id]);

        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function securityRotate2FA(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $id = $data['id'] ?? 0;

        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[rand(0, 31)];
        }

        $pdo = \FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection::getConnection();
        $pdo->prepare("UPDATE users SET totp_secret = ? WHERE id = ?")->execute([$secret, $id]);

        $response->getBody()->write(json_encode(['success' => true, 'message' => 'New 2FA Secret Generated']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function securityDelete(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $id = $data['id'] ?? 0;

        // Prevent self-delete
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
            return $this->jsonError($response, 'Cannot delete yourself!');
        }

        $pdo = \FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection::getConnection();
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);

        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function jsonError($response, $msg)
    {
        $response->getBody()->write(json_encode(['error' => $msg]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
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

                // Relative path for UI
                $relativePath = str_replace(realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR, '', $file->getRealPath());
                $relativePath = str_replace('\\', '/', $relativePath); // Normalize

                // Description logic (simple)
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
