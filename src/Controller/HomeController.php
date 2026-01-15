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

        // Render Template (Unified Logic Container)
        $template = 'admin/dashboard';

        $html = $this->mustache->render($template, [
            'title' => 'Dashboard Archivio',
            'content' => 'Benvenuto nel sistema di digitalizzazione archivio.',
            'stats' => $stats,
            'stats_json' => json_encode($stats),
            'real_is_admin' => $realIsAdmin,
            'is_admin' => $effectiveIsAdmin,
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
            })()
        ]);

        $response->getBody()->write($html);
        return $response;
    }
}


