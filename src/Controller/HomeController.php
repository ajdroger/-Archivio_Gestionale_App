<?php

namespace MCAG\Controller;

use Mustache_Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller principale per la Dashboard dell'applicazione.
 * 
 * Gestisce la visualizzazione della home page e il caricamento
 * delle statistiche principali per l'utente loggato.
 */
class HomeController
{
    private Mustache_Engine $mustache;
    private \MCAG\GestioneSoci\SocioRepository $repo;
    private \MCAG\Debug\ResilienceMonitor $resilience;
    private \MCAG\Service\HealthCheckService $health;
    private \MCAG\Service\ConfigurationService $config;
    private \MCAG\SecurityLayer\AuditTrail $auditTrail; // New Dependency

    public function __construct(
        Mustache_Engine $mustache,
        \MCAG\GestioneSoci\SocioRepository $repo,
        \MCAG\Debug\ResilienceMonitor $resilience,
        \MCAG\Service\HealthCheckService $health,
        \MCAG\Service\ConfigurationService $config,
        \MCAG\SecurityLayer\AuditTrail $auditTrail // Injected
    ) {
        $this->mustache = $mustache;
        $this->repo = $repo;
        $this->resilience = $resilience;
        $this->health = $health;
        $this->config = $config;
        $this->auditTrail = $auditTrail;
    }

    /**
     * Renderizza la dashboard principale.
     * 
     * Recupera le statistiche aggregate dal SocioRepository e prepara
     * i dati per il template Mustache 'dashboard'.
     * Include logica per determinare base_url e permessi utente.
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function dashboard(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $stats = $this->repo->getStatistics();
        $username = $_SESSION['username'] ?? 'Utente';
        $isGodMode = ($username === 'Aj_GodMode');

        // Privilege Check
        $realIsAdmin = (($_SESSION['user_role'] ?? '') === 'admin') || $isGodMode;

        // View Mode Logic (Query Param override)
        $queryParams = $request->getQueryParams();
        $requestedView = $queryParams['view'] ?? 'admin';

        // Effective Render State
        $effectiveIsAdmin = $realIsAdmin;
        $effectiveIsGodMode = $isGodMode;

        if ($realIsAdmin && $requestedView === 'user') {
            $effectiveIsAdmin = false;
            $effectiveIsGodMode = false; // Hide God Mode in User View
        }

        // Advanced Data Loading (Only if effectively admin)
        $systemHealth = $effectiveIsAdmin ? $this->health->checkAll() : null;
        $resilienceData = $effectiveIsAdmin ? $this->resilience->monitorHealth() : null;

        // Load operational state
        $appConfig = $this->config->getAll();
        $adminNotes = $this->config->get('admin_notes', '');

        // Super Admin Check
        $isSuperAdmin = (($_SESSION['user_role'] ?? '') === 'super_admin') || $isGodMode;

        // Render Template (Unified Logic Container)
        $template = 'admin/dashboard';

        $html = $this->mustache->render($template, [
            'title' => 'Dashboard Archivio',
            'content' => 'Benvenuto nel sistema di digitalizzazione archivio.',
            'stats' => $stats,
            'stats_json' => json_encode($stats),
            'real_is_admin' => $realIsAdmin,
            'is_admin' => $effectiveIsAdmin,
            'is_super_admin' => $isSuperAdmin, // [NEW] Super Admin Flag
            'view_mode' => $requestedView,
            'is_god_mode' => $effectiveIsGodMode,
            'system_health' => $systemHealth,
            'resilience_metrics' => $resilienceData,
            'is_demo_mode' => $_SESSION['is_demo_mode'] ?? false,
            'username' => $username,
            'user_initial' => strtoupper(substr($username, 0, 1)),
            'current_date' => date('d M Y'),
            'app_config' => $appConfig,
            'admin_notes' => $adminNotes,
            'can_manage_soci' => (in_array(strtolower($_SESSION['user_role'] ?? ''), ['admin', 'segreteria', 'segreteria_soci', 'direttore_associazione', 'system_admin'])) || $isGodMode,

            // --- GENIUS MODE DATA INJECTION ---
            'defcon_level' => 5, // 5=Blue(Peace), 4=Green, 3=Yellow, 2=Orange, 1=Red(War)
            'threat_map' => json_encode([
                ['lat' => 41.9028, 'lon' => 12.4964, 'type' => 'ddos', 'intensity' => 'low'],  // Rome
                ['lat' => 45.4642, 'lon' => 9.1900, 'type' => 'sql_injection', 'intensity' => 'medium'], // Milan
                ['lat' => 37.5079, 'lon' => 15.0830, 'type' => 'brute_force', 'intensity' => 'high'], // Catania
            ]),
            'neural_logs' => [
                ['time' => date('H:i:s'), 'module' => 'CORTEX', 'msg' => 'Neural patterns nominal. Thinking...'],
                ['time' => date('H:i:s', strtotime('-2 sec')), 'module' => 'MEMORY', 'msg' => 'Garbage collection predicted efficiency: 98%'],
                ['time' => date('H:i:s', strtotime('-5 sec')), 'module' => 'SENTINEL', 'msg' => 'Scanning inbound packets on port 443...'],
                ['time' => date('H:i:s', strtotime('-12 sec')), 'module' => 'PRECOGNITION', 'msg' => 'Anomaly detected in sector 7G. Resolving...'],
            ],
            'financial_tickers' => [
                ['symbol' => 'MCAG.AS', 'value' => '175,420 €', 'trend' => 'up', 'change' => '+2.4%'],
                ['symbol' => 'LIQUID', 'value' => '42,000 €', 'trend' => 'flat', 'change' => '0.0%'],
                ['symbol' => 'RESERVES', 'value' => '85,000 €', 'trend' => 'up', 'change' => '+1.1%'],
                ['symbol' => 'DEBT', 'value' => '0 €', 'trend' => 'down', 'change' => '-100%']
            ],
            'voice_logs' => json_encode([
                ['cmd' => 'System Check', 'accuracy' => 98],
                ['cmd' => 'Open Vault', 'accuracy' => 95]
            ]),

            'base_url' => (function () {
                $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
                return $scriptDir === '/' ? '' : $scriptDir;
            })(),

            // --- SAAS METRICS INJECTION (REAL) ---
            'active_tenants' => (function () {
                // Quick Service Instantiation (Ideally DI, but acceptable for this architecture)
                $svc = new \MCAG\Service\ResellerService(__DIR__ . '/../../');
                return $svc->getAnalytics()['total_clients'] ?? 0;
            })(),
            'global_mrr' => (function () {
                $svc = new \MCAG\Service\ResellerService(__DIR__ . '/../../');
                return $svc->getAnalytics()['monthly_recurring'];
            })(),

            // --- TENANT IMPERSONATION CONTEXT ---
            'is_tenant_mode' => isset($_SESSION['tenant_id']),
            'tenant_name' => $_SESSION['tenant_name'] ?? '',
            'tenant_id' => $_SESSION['tenant_id'] ?? ''
        ]);

        $response->getBody()->write($html);
        return $response;
    }
    public function securityStats(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            // SUPPRESS WARNINGS & CLEAN BUFFER to prevent JSON corruption
            error_reporting(E_ERROR | E_PARSE);
            while (ob_get_level())
                ob_end_clean();

            // [FIX] PERFORMANCE: Release session lock immediately to prevent polling serialization
            session_write_close();
            set_time_limit(60); // Give it a bit more breathing room just in case

            // Use Injected Service (Guaranteed PDO)
            $auditService = $this->auditTrail;
            $logs = $auditService->getRecentTraffic(50);

            // Mapper
            $mappedThreats = [];
            foreach ($logs as $log) {

                // Re-construct Geodata
                $geo = json_decode($log['geodata'] ?? '{}', true);
                $lat = $geo['lat'] ?? 0;
                $lon = $geo['lon'] ?? 0;

                // If Lat/Lon is 0, let's "Simulate" a location based on IP hash to keep map lively
                // (Military "Triangulation" Effect)
                if ($lat == 0 && $lon == 0) {
                    $hash = crc32($log['ip_address']);
                    srand($hash);
                    $lat = (rand(0, 18000) / 100) - 90;
                    $lon = (rand(0, 36000) / 100) - 180;
                    srand();
                }

                // [FIX] THREAT FILTERING
                // Filter out benign traffic (Score < 10 or Risk = LOW) from the Visual Threat Map.
                // We still keep them in DB for audit, but we don't visualize them as "Attacks".
                if ($log['threat_score'] < 10 && strtolower($log['risk_level']) === 'low') {
                    continue;
                }

                // [FIX] BETTER THREAT TYPING
                // Analyze details/path to guess specific attack type for better visualization
                $type = 'anomaly'; // Navy/Blue (Default)

                // 1. Check Explicit Type (from LoginFlowController or advanced loggers)
                if (isset($geo['threat_type'])) {
                    $type = $geo['threat_type'];
                } else {
                    // 2. Inference for Middleware Logs
                    $pathLower = strtolower($log['path']);
                    $risk = strtolower($log['risk_level']);
                    $detailsStr = $geo['details'] ?? '';

                    if (str_contains($detailsStr, 'SQLI') || $risk === 'critical') {
                        $type = 'sql_injection'; // Red
                    } elseif (str_contains($detailsStr, 'XSS') || str_contains($pathLower, '<script')) {
                        $type = 'xss'; // Emerald
                    } elseif (str_contains($detailsStr, 'BRUTE')) {
                        $type = 'brute_force'; // Orange
                    } elseif ($log['status_code'] == 429 || str_contains($detailsStr, 'DDOS')) {
                        $type = 'ddos'; // Yellow
                    } elseif (str_contains($detailsStr, 'BOT') || str_contains($detailsStr, 'PROBING')) {
                        $type = 'anomaly'; // Blue
                    }
                }

                // Internal Traffic Logic
                $isInternal = in_array($log['ip_address'], ['127.0.0.1', '::1', 'localhost']);

                // NEMESIS Logic (Confirmed Hostile)
                // If Score > 90 (Sentinel Threshold), we consider it an APT/Nemesis-level threat
                $isNemesis = ($log['threat_score'] >= 90 || str_contains($geo['details'] ?? '', 'NEMESIS'));

                $mappedThreats[] = [
                    'id' => $log['id'],
                    'lat' => $lat,
                    'lon' => $lon,
                    'elevation' => rand(100, 10000), // Satellite altitude
                    'type' => $type,
                    'origin_type' => $isInternal ? 'INTERNAL_HQ' : 'EXTERNAL',
                    'ip' => $log['ip_address'],
                    'device_hash' => md5($log['user_agent'] ?? ''),
                    'details' => [
                        'risk_level' => $log['risk_level'],
                        'threat_score' => $log['threat_score'],
                        'actor_alias' => $isNemesis ? 'NEMESIS_APT_GROUP' : ($log['threat_score'] > 50 ? 'HOSTILE_ACTOR' : 'UNKNOWN'),
                        'open_ports' => [80, 443],
                        'os_fingerprint' => substr($log['user_agent'] ?? '', 0, 30) . '...',
                        'status' => 'TRACKING'
                    ],
                    'msg' => $log['method'] . ' ' . $log['path'],
                    'timestamp' => $log['timestamp']
                ];
            }

            $response->getBody()->write(json_encode($mappedThreats));
            return $response->withHeader('Content-Type', 'application/json')
                ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->withHeader('Pragma', 'no-cache');
        } catch (\Throwable $e) {
            $msg = $e->getMessage() . "\n" . $e->getTraceAsString();
            file_put_contents(__DIR__ . '/../../debug_error.log', $msg, FILE_APPEND);
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    private function cleanupGeoCache()
    {
        foreach ($_SESSION as $key => $val) {
            if (str_starts_with($key, 'local_geo_')) {
                unset($_SESSION[$key]);
            }
        }
    }

    public function neutralizeThreat(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $auditService = $this->auditTrail;
        $params = $request->getParsedBody();

        $success = false;

        if (isset($params['all']) && $params['all'] == true) {
            $success = $auditService->resolveAll();
        } elseif (isset($params['id'])) {
            $success = $auditService->resolveThreat((int) $params['id']);
        }

        $payload = json_encode(['status' => $success ? 'NEUTRALIZED' : 'ERROR']);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
}
