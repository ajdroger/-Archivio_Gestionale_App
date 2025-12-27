<?php

namespace FratellanzaMilitare\Controller\Intelligence;

use FratellanzaMilitare\GestioneSoci\SocioRepository;
use Mustache_Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller dedicato all'analisi dati e visualizzazione KPI.
 */
class StatsDashboardController
{
    private Mustache_Engine $mustache;
    private SocioRepository $repository;

    public function __construct(Mustache_Engine $mustache, SocioRepository $repository)
    {
        $this->mustache = $mustache;
        $this->repository = $repository;
    }

    /**
     * Gestisce la visualizzazione della Dashboard di Intelligence.
     * 
     * Questa funzione orchestra il recupero dei dati statistici, la gestione della cache
     * e la preparazione (mapping) dei dati per la vista.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function view(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // 1. Recupero Parametri di Filtro dalla Query String
        $params = $request->getQueryParams();
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
            'is_admin' => ($_SESSION['user_role'] ?? '') === 'admin',
            'username' => $_SESSION['username'] ?? 'Utente',
            'user_initial' => strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1))
        ]);

        $response->getBody()->write($html);
        return $response;
    }
}
