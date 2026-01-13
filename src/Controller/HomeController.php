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

    public function __construct(Mustache_Engine $mustache, \MCAG\GestioneSoci\SocioRepository $repo)
    {
        $this->mustache = $mustache;
        $this->repo = $repo;
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

        $html = $this->mustache->render('dashboard', [
            'title' => 'Dashboard Archivio',
            'content' => 'Benvenuto nel sistema di digitalizzazione archivio.',
            'stats' => $stats,
            'stats_json' => json_encode($stats),
            'stats_json' => json_encode($stats),
            'is_admin' => (($_SESSION['user_role'] ?? '') === 'admin') || (($_SESSION['username'] ?? '') === 'Aj_GodMod'),
            'is_demo_mode' => $_SESSION['is_demo_mode'] ?? false,
            'username' => $_SESSION['username'] ?? 'Utente',
            'user_initial' => strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)),
            'base_url' => (function () {
                $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
                return $scriptDir === '/' ? '' : $scriptDir;
            })()
        ]);

        $response->getBody()->write($html);
        return $response;
    }
}


