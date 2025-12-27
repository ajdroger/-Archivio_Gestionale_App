<?php

namespace FratellanzaMilitare\Controller\Intelligence;

use FratellanzaMilitare\GestioneSoci\SocioRepository;
use FratellanzaMilitare\Debug\ResilienceMonitor;
use FratellanzaMilitare\Service\HealthCheckService;
use Mustache_Engine;
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

    public function __construct(
        Mustache_Engine $mustache,
        SocioRepository $repository,
        ResilienceMonitor $resilienceMonitor,
        HealthCheckService $healthCheck
    ) {
        $this->mustache = $mustache;
        $this->repository = $repository;
        $this->resilienceMonitor = $resilienceMonitor;
        $this->healthCheck = $healthCheck;
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
        $sociFilters = [];

        // Mappatura dei filtri UI ai filtri del Repository
        if (!empty($params['status'])) {
            $sociFilters['stato'] = $params['status'];
        }
        if (!empty($params['payment_status'])) {
            $sociFilters['moroso'] = ($params['payment_status'] === 'moroso');
        }

        // 2. Strategia di Caching (Performance Optimization)
        // Evitiamo di ricalcolare le statistiche pesanti (COUNT implicite) ad ogni refresh.
        // La cache vive per 300 secondi (5 minuti).
        $cacheFile = __DIR__ . '/../../../../var/cache/stats_cache.json';
        $useCache = empty($params['refresh']) && (php_sapi_name() !== 'cli');

        if ($useCache && file_exists($cacheFile) && (time() - filemtime($cacheFile) < 300)) {
            // HIT: Recupero dati dalla cache
            $stats = json_decode(file_get_contents($cacheFile), true);
        } else {
            // MISS: Ricalcolo statistiche dal DB
            $stats = $this->repository->getStatistics();

            // Assicuriamoci che la directory di cache esista
            $dir = dirname($cacheFile);
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            // Scrittura atomica (o quasi) della cache
            @file_put_contents($cacheFile, json_encode($stats));
        }

        // 3. Recupero Lista Soci Filtrata
        // Questa query è dinamica e dipende dai filtri, quindi non viene cachata qui.
        $filteredSoci = $this->repository->findByFilters($sociFilters);

        // 4. ViewModel Mapping (Mustache Compatibility Layer)
        // I dati del dominio (Enum, DateTime) devono essere convertiti in scalari/array
        // per essere digeriti correttamente dal template engine Mustache.
        $sociViewModel = array_map(function ($socio) {
            return [
                'DatiPersonali' => [
                    'Nome' => $socio->DatiPersonali->Nome,
                    'Cognome' => $socio->DatiPersonali->Cognome,
                    'Email' => $socio->DatiPersonali->Email,
                ],
                'Matricola' => $socio->Matricola,
                'CodiceFiscale' => $socio->CodiceFiscale,
                'Stato' => [
                    'name' => $socio->Stato->name, // Converte l'Enum in stringa
                    'isActive' => $socio->Stato->name === 'ATTIVO' // Flag booleano per la UI
                ],
                'verificaMorosita' => $socio->verificaMorosita() // Calcolo dinamico morosità
            ];
        }, $filteredSoci);

        // 5. Rendering della Vista
        $html = $this->mustache->render('statistics', [
            'stats' => $stats,
            'filtered_soci' => $sociViewModel,
            'filtered_count' => count($filteredSoci),
            'filters' => $params,
            'monitoring' => $this->resilienceMonitor->monitorHealth(),
            'health' => $this->healthCheck->checkAll(),
            'is_admin' => ($_SESSION['user_role'] ?? '') === 'admin',
            'username' => $_SESSION['username'] ?? 'Utente',
            'user_initial' => strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1))
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
