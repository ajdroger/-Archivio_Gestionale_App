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
    private DevToolsSystemController $systemController;
    private DevToolsAuditController $auditController;

    public function __construct(Mustache_Engine $mustache, DevToolsSystemController $systemController, DevToolsAuditController $auditController)
    {
        $this->mustache = $mustache;
        $this->systemController = $systemController;
        $this->auditController = $auditController;
    }

    public function dashboard(Request $request, Response $response): Response
    {
        // Delegated Logic
        $systemInfo = $this->systemController->getSystemInfo();
        $health = $this->systemController->getHealth();
        $schemaStats = $this->systemController->getSchemaStats();
        $logs = $this->systemController->getRecentLogs();
        $sessionDebug = $this->systemController->getSessionDebug();
        // Simplified scripts Scan for dashboard summary (or full)
        // For now, let's keep it minimal or call full scan if needed
        $scripts = $this->systemController->scanScripts();

        $auditResult = $this->auditController->getLogs($request); // Get logs using Request params
        $auditLogs = $auditResult['data'];

        $params = $request->getQueryParams();

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
            'session_debug' => $sessionDebug,
            'body_class' => 'devtools-page'
        ]);

        $response->getBody()->write($html);
        return $response;
    }
}
