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

        $html = $this->mustache->render('dashboard', [
            'title' => 'Dashboard Archivio',
            'content' => 'Benvenuto nel sistema di digitalizzazione archivio.',
            'stats' => $stats,
            'stats_json' => json_encode($stats),
            'real_is_admin' => $realIsAdmin, // Keeps the toggles visible
            'is_admin' => $effectiveIsAdmin, // Controls the view
            'view_mode' => $requestedView, // For button styling
            'is_god_mode' => $effectiveIsGodMode,
            'system_health' => $systemHealth,
            'resilience_metrics' => $resilienceData,
            'is_demo_mode' => $_SESSION['is_demo_mode'] ?? false,
            'username' => $username,
            'user_initial' => strtoupper(substr($username, 0, 1)),
            'current_date' => date('d M Y'),
            'app_config' => $appConfig, // Pass full config
            'admin_notes' => $adminNotes,
            'base_url' => (function () {
                $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
                return $scriptDir === '/' ? '' : $scriptDir;
            })()
        ]);

        $response->getBody()->write($html);
        return $response;
    }
}


