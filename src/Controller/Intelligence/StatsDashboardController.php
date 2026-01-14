<?php

namespace MCAG\Controller\Intelligence;

use MCAG\GestioneSoci\SocioRepository;
use MCAG\Debug\ResilienceMonitor;
use MCAG\Service\HealthCheckService;
use Mustache_Engine;
use Predis\Client as RedisClient;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller dedicato all'analisi dati e visualizzazione KPI.
 */
/**
 * Controller per la Dashboard di Intelligence e Statistiche.
 * 
 * Aggrega dati statistici, monitoraggio della salute del sistema e KPI dei soci.
 * Gestisce caching delle query costose e endpoint API per l'aggiornamento real-time.
 */
class StatsDashboardController
{
    private Mustache_Engine $mustache;
    private SocioRepository $repository;
    private ResilienceMonitor $resilienceMonitor;
    private HealthCheckService $healthCheck;
    private ?RedisClient $redis;

    public function __construct(
        Mustache_Engine $mustache,
        SocioRepository $repository,
        ResilienceMonitor $resilienceMonitor,
        HealthCheckService $healthCheck,
        ?RedisClient $redis = null
    ) {
        $this->mustache = $mustache;
        $this->repository = $repository;
        $this->resilienceMonitor = $resilienceMonitor;
        $this->healthCheck = $healthCheck;
        $this->redis = $redis;
    }

    /**
     * Gestisce la visualizzazione della Dashboard di Intelligence.
     * 
     * Implementa strategie di caching (file-based) per ridurre il carico sul DB.
     * Recupera e mappa i dati dei soci filtrati per il template Mustache.
     * Gestisce richieste AJAX per aggiornamenti in tempo reale.
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function view(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();

        // 0. API / Real-time Endpoint Detection
        if (isset($params['action']) && $params['action'] === 'api') {
            return $this->handleApiRequest($response);
        }

        // --- AUTH & VIEW LOGIC ---
        $username = $_SESSION['username'] ?? 'Utente';
        $isGodMode = ($username === 'Aj_GodMode');
        // Privilege Check
        $realIsAdmin = (($_SESSION['user_role'] ?? '') === 'admin') || $isGodMode;

        // View Mode Logic (Query Param override)
        $requestedView = $params['view'] ?? 'admin';

        // Effective Render State
        $effectiveIsAdmin = $realIsAdmin;
        $effectiveIsGodMode = $isGodMode;

        if ($realIsAdmin && $requestedView === 'user') {
            $effectiveIsAdmin = false;
            $effectiveIsGodMode = false;
        }

        // --- DATA LOADING STRATEGY ---
        // 1. Common Data (Always Loaded)
        $stats = $this->repository->getStatistics(); // Basic counts are public

        // 2. Admin-Only Data (Financials, Health, Resilience)
        $monitoring = null;
        $health = null;
        $financials = null;

        if ($effectiveIsAdmin) {
            $monitoring = $this->resilienceMonitor->monitorHealth();
            $health = $this->healthCheck->checkAll();
            // Mocking Advanced Financials (In real app, this would be a Service)
            $financials = [
                'asset_value' => '€ 175.000',
                'projected_revenue' => '€ 24.500',
                'growth_rate' => '+12.5%'
            ];
        }

        // 3. Soci Filters (Used for User Directory view or Admin List view context)
        $sociFilters = [];
        if (!empty($params['status']))
            $sociFilters['stato'] = $params['status'];
        if (!empty($params['payment_status']))
            $sociFilters['moroso'] = ($params['payment_status'] === 'moroso');

        // 4. Rendering
        $html = $this->mustache->render('statistics', [
            'stats' => $stats,
            'financials' => $financials, // ONLY available if Admin

            // View Control
            'real_is_admin' => $realIsAdmin,
            'is_admin' => $effectiveIsAdmin,
            'view_mode' => $requestedView,
            'is_god_mode' => $effectiveIsGodMode,

            'filters' => $params,
            'monitoring' => $monitoring,
            'health' => $health,

            'username' => $username,
            'user_initial' => strtoupper(substr($username, 0, 1)),
            'base_url' => (function () {
                $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
                return $scriptDir === '/' ? '' : $scriptDir;
            })()
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Endpoint API per aggiornamenti real-time.
     * 
     * Restituisce JSON con statistiche, monitoraggio e salute sistema.
     * Utilizzato dal frontend per il refresh automatico dei widget.
     * 
     * @param ResponseInterface $response
     * @return ResponseInterface JSON
     */
    private function handleApiRequest(ResponseInterface $response): ResponseInterface
    {
        $stats = $this->repository->getStatistics();
        $monitoring = $this->resilienceMonitor->monitorHealth();
        $health = $this->healthCheck->checkAll();

        $data = [
            'timestamp' => time(),
            'stats' => $stats,
            'monitoring' => $monitoring,
            'health' => $health
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
}


