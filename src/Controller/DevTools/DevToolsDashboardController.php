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
/**
 * Controller principale del pannello DevTools.
 * 
 * Aggrega tutte le funzionalità di debug e amministrazione:
 * System Info, Database, Audit, FileSystem e Code Reactor.
 */
class DevToolsDashboardController
{
    private Mustache_Engine $mustache;
    private DevToolsSystemController $systemController;
    private DevToolsAuditController $auditController;
    private \FratellanzaMilitare\Service\Demo\DemoInvitationService $demoService;

    public function __construct(
        Mustache_Engine $mustache,
        DevToolsSystemController $systemController,
        DevToolsAuditController $auditController,
        \FratellanzaMilitare\Service\Demo\DemoInvitationService $demoService
    ) {
        $this->mustache = $mustache;
        $this->systemController = $systemController;
        $this->auditController = $auditController;
        $this->demoService = $demoService;
    }

    /**
     * Renderizza la Dashboard completa dei DevTools.
     * 
     * Recupera dati da tutti i sotto-controller (System, Audit, ecc.)
     * per costruire la vista unificata 'devtools'.
     * 
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function dashboard(Request $request, Response $response): Response
    {
        if ($_SESSION['is_demo_mode'] ?? false) {
            $response->getBody()->write("Accesso a DevTools/Mission Control disabilitato in modalità Demo.");
            return $response->withStatus(403);
        }

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
            'body_class' => 'devtools-page',
            'base_url' => (function () {
                $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
                return $scriptDir === '/' ? '' : $scriptDir;
            })(),
            'csrf' => [
                'name' => $request->getAttribute('csrf_name'),
                'value' => $request->getAttribute('csrf_value')
            ]
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    public function auditAjax(Request $request, Response $response): Response
    {
        $auditResult = $this->auditController->getLogs($request);
        $response->getBody()->write(json_encode($auditResult));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Endpoint Heartbeat per monitoraggio Real-time.
     * 
     * Restituisce statistiche vitali (CPU, RAM, DB Latency, Intrusioni)
     * aggiornate ogni pochi secondi dalla dashboard.
     * 
     * @param Request $request
     * @param Response $response
     * @return Response JSON contenente metriche di sistema e sicurezza
     */
    public function heartbeat(Request $request, Response $response): Response
    {
        // Clean any accidental output (warnings, whitespace)
        if (ob_get_length())
            ob_clean();

        try {
            // Wrappers for safety to prevent single component failure from crashing output
            $sessions = -1;
            try {
                $sessions = $this->systemController->getActiveSessionsCount();
            } catch (\Throwable $e) {
                // Log simplified error if needed
            }

            $latency = ['db_ms' => -1, 'status' => 'error'];
            try {
                $latency = $this->systemController->getLatencyMetrics();
            } catch (\Throwable $e) {
            }

            $intrusion = ['count' => -1, 'status' => 'error'];
            try {
                $intrusion = $this->systemController->getIntrusionStats();
            } catch (\Throwable $e) {
            }

            $data = [
                'system' => $this->systemController->getSystemInfo(),
                'database_schema' => $this->systemController->getSchemaStats(),
                'health' => $this->systemController->getHealth(),
                'monitoring' => [
                    'sessions' => $sessions,
                    'latency' => $latency,
                    'intrusion' => $intrusion,
                    'privacy' => $this->systemController->getPrivacyStats(),
                    'redis' => $this->systemController->getRedisStats(),
                    'git' => $this->systemController->getGitInfo()
                ]
            ];

        } catch (\Throwable $e) {
            // Fatal panic fallback
            $data = [
                'error' => true,
                'message' => $e->getMessage(),
                'file' => $e->getFile(), // Keep file and line for better debugging in fallback
                'line' => $e->getLine()
            ];
        }

        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR | JSON_PARTIAL_OUTPUT_ON_ERROR));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
    }
    /**
     * Gestisce l'invio dell'invito Demo.
     */
    public function handleDemoInvite(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $email = $data['email'] ?? '';
        $clientName = $data['client_name'] ?? 'Cliente';

        if (empty($email)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Email obbligatoria.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $success = $this->demoService->sendInvite($email, $clientName);

        if ($success) {
            $msg = "Invito inviato correttamente a $email.";
        } else {
            $msg = "Errore durante l'invio. Controlla i log.";
        }

        $response->getBody()->write(json_encode(['success' => $success, 'message' => $msg]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
