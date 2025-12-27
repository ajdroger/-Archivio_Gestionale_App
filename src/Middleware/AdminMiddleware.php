<?php

namespace FratellanzaMilitare\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class AdminMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $userRole = $_SESSION['user_role'] ?? '';

        if ($userRole !== 'admin') {
            $response = new SlimResponse();

            // Se è una richiesta AJAX, restituiamo JSON
            if ($request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest' || str_contains($request->getUri()->getPath(), '/devtools/run')) {
                $response->getBody()->write(json_encode([
                    'error' => true,
                    'message' => 'Accesso negato: Autorizzazioni insufficienti.',
                    'output' => '[403 FORBIDDEN]'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            // Altrimenti redirect alla home con messaggio di errore (se implementato)
            $routeParser = \Slim\Routing\RouteContext::fromRequest($request)->getRouteParser();
            return $response->withHeader('Location', $routeParser->urlFor('dashboard'))->withStatus(302);
        }

        return $handler->handle($request);
    }
}
