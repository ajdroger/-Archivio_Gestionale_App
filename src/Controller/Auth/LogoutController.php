<?php

namespace FratellanzaMilitare\Controller\Auth;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;

/**
 * Controller dedicato alla terminazione della sessione.
 */
class LogoutController
{
    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        return $response->withHeader('Location', $routeParser->urlFor('login'))->withStatus(302);
    }
}
