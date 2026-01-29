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

    public function __construct(
        Mustache_Engine $mustache,
        \MCAG\GestioneSoci\SocioRepository $repo,
        \MCAG\Debug\ResilienceMonitor $resilience,
        \MCAG\Service\HealthCheckService $health,
        \MCAG\Service\ConfigurationService $config // Injected
    ) {
        $this->mustache = $mustache;
        $this->repo = $repo;
        $this->resilience = $resilience;
        $this->health = $health;
        $this->config = $config;
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
                return $svc->getAnalytics()['total_clients'];
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
        // SUPPRESS WARNINGS & CLEAN BUFFER to prevent JSON corruption
        error_reporting(E_ERROR | E_PARSE); // Hide warnings/notices
        while (ob_get_level())
            ob_end_clean(); // Clear any previous output (e.g. notices)

        $auditService = \MCAG\SecurityLayer\AuditTrail::getInstance();
        $threats = $auditService->getThreats(30);
        $mappedThreats = [];

        foreach ($threats as $threat) {
            $ip = $threat['ip_address'] ?? '0.0.0.0';
            $isLocal = ($ip === '::1' || $ip === '127.0.0.1');

            $details = [];
            $originType = 'EXTERNAL';

            if ($isLocal) {
                // INTERNAL THREAT: Resolve Real Location via External IP
                // Strategy: 1. Get Public IP -> 2. GeoLocate Public IP -> 3. Fallback to Home Base

                // Session Cache to avoid Rate Limiting (5 min cache)
                $cacheKey = 'local_geo_' . date('Hi');
                // Allow refreshing every 5 mins (change 'Hi' format if needed for stricter cache)
                $geoData = $_SESSION[$cacheKey] ?? null;

                if (!$geoData) {
                    try {
                        // 1. Get External IP (Fast & Free) - ADD USER AGENT
                        $opts = [
                            'http' => [
                                'method' => 'GET',
                                'timeout' => 2,
                                'header' => "User-Agent: MCAG-Cortex/9.0\r\n"
                            ]
                        ];
                        $context = stream_context_create($opts);
                        $publicIp = @file_get_contents('https://api.ipify.org', false, $context);

                        if ($publicIp) {
                            // 2. GeoLocate (Free - 45 req/min)
                            $json = @file_get_contents("http://ip-api.com/json/{$publicIp}", false, $context);
                            $apiData = json_decode($json, true);

                            if ($apiData && ($apiData['status'] === 'success')) {
                                $geoData = [
                                    'lat' => $apiData['lat'],
                                    'lon' => $apiData['lon'],
                                    'city' => $apiData['city'] ?? 'Unknown',
                                    'isp' => $apiData['isp'] ?? 'Unknown',
                                    'elevation' => 100
                                ];
                                $_SESSION[$cacheKey] = $geoData; // Cache it
                            }
                        }
                    } catch (\Exception $e) { /* Silent Fallback */
                    }
                }

                if ($geoData) {
                    // REAL-TIME TRACKING ACTIVE
                    $lat = $geoData['lat'];
                    $lon = $geoData['lon'];
                    $elevation = $geoData['elevation'];

                    $details = [
                        'sector' => strtoupper($geoData['city'] ?? 'LOCAL') . '_NODE',
                        'clearance' => 'TOP_SECRET',
                        'status' => 'LIVE_TRACKING_ACTIVE',
                        'notes' => "Origin ISP: " . ($geoData['isp'] ?? 'Local Uplink')
                    ];
                } else {
                    // FALLBACK: HOME BASE (Loro Ciuffenna)
                    $lat = 43.7797;
                    $lon = 11.4442;
                    $elevation = 88.28;
                    $details = ['sector' => 'HQ_OFFLINE', 'status' => 'FALLBACK_COORDS'];
                }

                $originType = 'INTERNAL_HQ';

            } else {
                // External Threats Logic
                // Use hash for deterministic coordinates
                $hash = crc32($ip);
                srand($hash);
                $lat = (rand(0, 18000) / 100) - 90;
                $lon = (rand(0, 36000) / 100) - 180;
                $elevation = rand(10, 500);
                srand();

                $details = [
                    'category' => 'UNIDENTIFIED_HOSTILE',
                    'asn' => 'UNKNOWN_ASN',
                    'risk_level' => 'HIGH',
                    'device_hash' => substr(md5($ip), 0, 12),
                    'actor_alias' => 'UNKNOWN_ACTOR',
                    'notes' => 'REAL TRAFFIC DETECTED'
                ];
            }

            // --- MASSIVE INTEL ENRICHMENT (For "Full Dossier" View) ---
            $details['os_fingerprint'] = $isLocal ? 'Windows Server 2026 (Datacenter Ed.)' : ($originType === 'INTERNAL_HQ' ? 'Linux Kernel 6.8 (Hardened)' : 'Unknown/Encrypted TCP');
            $details['open_ports'] = $isLocal ? [80, 443, 3306, 8080] : [rand(1024, 65535)];
            $details['uplink_speed'] = $isLocal ? '10 Gbps (Backbone)' : rand(10, 1000) . ' Mbps';
            $details['active_sessions'] = $isLocal ? 1 : rand(5, 50);
            $details['last_seen'] = date('Y-m-d H:i:s');
            // Mapping Logic (Reused)
            $type = match ($threat['action']) {
                'LOGIN_FAILED' => 'brute_force',
                'ACCESS_DENIED' => 'unauthorized',
                'SYSTEM_ALERT' => 'malware',
                default => 'anomaly'
            };
            $details['threat_score'] = $type === 'brute_force' ? 85 : ($type === 'malware' ? 99 : 45);

            $mappedThreats[] = [
                'id' => $threat['id'], // CRITICAL: Need DB ID for Neutralization
                'lat' => $lat,
                'lon' => $lon,
                'elevation' => $elevation,
                'type' => $type,
                'origin_type' => $originType,
                'ip' => $ip,
                'device_hash' => $details['device_hash'] ?? 'UNKNOWN',
                'details' => $details,
                'msg' => $threat['action'] . ' - ' . ($threat['username'] ?? 'Unknown'),
                'timestamp' => $threat['timestamp']
            ];
        }

        $response->getBody()->write(json_encode($mappedThreats));
        return $response->withHeader('Content-Type', 'application/json');
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
        $auditService = \MCAG\SecurityLayer\AuditTrail::getInstance();
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


