<?php

namespace FratellanzaMilitare\Controller\Auth;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Attributes as OA;

/**
 * Gestisce l'avvio della Modalità Demo Restrittiva.
 * 
 * Permette l'accesso senza login ma con privilegi limitati.
 */
class DemoModeController
{
    /**
     * Avvia una sessione Demo.
     * 
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    #[OA\Get(
        path: '/auth/start-demo',
        summary: 'Avvia la modalità Demo',
        description: 'Avvia una sessione ospite limitata senza password per testare l\'applicazione.',
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 302, description: 'Redirect alla Dashboard')
        ]
    )]
    public function startDemo(Request $request, Response $response): Response
    {
        // 1. Distruggi sessione esistente (se presente)
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }

        // 2. Avvia nuova sessione pulita
        session_start();
        session_regenerate_id(true);

        // 3. Imposta credenziali Demo
        $_SESSION['user_id'] = 0; // ID fittizio
        $_SESSION['username'] = 'Utente Demo';
        $_SESSION['user_role'] = 'demo'; // Ruolo speciale
        $_SESSION['is_demo_mode'] = true; // Flag globale per UI

        // Logga l'azione (opzionale, se avessimo accesso al logger qui)
        // error_log("Demo Mode avviata da " . $_SERVER['REMOTE_ADDR']);

        // 4. Redirect alla Dashboard usando il RouteParser per supportare sottocartelle
        try {
            $routeParser = \Slim\Routing\RouteContext::fromRequest($request)->getRouteParser();
            $url = $routeParser->urlFor('dashboard');
        } catch (\Throwable $e) {
            // Fallback se il routing fallisce
            $url = '/';
        }

        return $response
            ->withHeader('Location', $url)
            ->withStatus(302);
    }
}
