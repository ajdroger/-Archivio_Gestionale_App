<?php

namespace MCAG\Controller\Auth;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;

/**
 * Controller dedicato alla terminazione della sessione.
 */
/**
 * Gestisce il logout dell'utente.
 * 
 * Termina la sessione corrente e reindirizza alla pagina di login.
 */
class LogoutController
{
    /**
     * Esegue il logout.
     * 
     * Distrugge la sessione PHP se attiva e reindirizza.
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            \MCAG\SecurityLayer\SessionManager::destroy();
        }

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        return $response->withHeader('Location', $routeParser->urlFor('login'))->withStatus(302);
    }
}


