<?php

namespace FratellanzaMilitare\Controller;

use Mustache_Engine;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SettingsController
{
    private Mustache_Engine $mustache;

    public function __construct(Mustache_Engine $mustache)
    {
        $this->mustache = $mustache;
    }

    public function view(Request $request, Response $response): Response
    {
        // Mock User Data (In a real app, strict typing from AuthMiddleware)
        $user = [
            'username' => 'admin',
            'role' => 'Amministratore',
            'email' => 'admin@fratellanza.it',
            'last_login' => date('d/m/Y H:i')
        ];

        // CSRF Tokens


        $html = $this->mustache->render('settings', [
            'title' => 'Impostazioni Profilo',
            'user' => $user,


            'is_admin' => ($_SESSION['user_role'] ?? '') === 'admin',
            'username' => $_SESSION['username'] ?? 'Utente',
            'user_initial' => strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1))
        ]);

        $response->getBody()->write($html);
        return $response;
    }
}
