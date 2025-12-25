<?php

namespace FratellanzaMilitare\Controller;

use Mustache_Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HomeController
{
    private Mustache_Engine $mustache;
    private \FratellanzaMilitare\GestioneSoci\SocioRepository $repo;

    public function __construct(Mustache_Engine $mustache, \FratellanzaMilitare\GestioneSoci\SocioRepository $repo)
    {
        $this->mustache = $mustache;
        $this->repo = $repo;
    }

    public function dashboard(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $stats = $this->repo->getStatistics();

        $html = $this->mustache->render('dashboard', [
            'title' => 'Dashboard Archivio',
            'content' => 'Benvenuto nel sistema di digitalizzazione archivio.',
            'stats' => $stats,
            'stats_json' => json_encode($stats),
            'is_admin' => ($_SESSION['user_role'] ?? '') === 'admin',
            'username' => $_SESSION['username'] ?? 'Utente',
            'user_initial' => strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1))
        ]);

        $response->getBody()->write($html);
        return $response;
    }
}
