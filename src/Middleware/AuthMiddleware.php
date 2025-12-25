<?php

namespace FratellanzaMilitare\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        // Controllo Sessione Semplificato
        // Nella simulazione, assumiamo che se esiste una specifica chiave di sessione, l'utente è loggato.
        // Per scopi di sviluppo/demo, lo salteremo se non stiamo simulando esplicitamente un flusso di login.
        // O meglio: iniziamo con un bypass per ora, ma strutturiamolo correttamente.

        // Session is already started in index.php


        if (!isset($_SESSION['user_id'])) {
            $path = $request->getUri()->getPath();
            // Allow login routes
            if (strpos($path, '/login') !== false || $path === '/fratellanza-militare-archivio/public/login') {
                return $handler->handle($request);
            }

            $response = new SlimResponse();
            return $response->withHeader('Location', '/fratellanza-militare-archivio/public/login')->withStatus(302);
        }

        return $handler->handle($request);
    }
}
