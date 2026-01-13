<?php

namespace MCAG\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Middleware Placeholder per Autenticazione JWT (Auth0 / Firebase).
 * 
 * TODO: Decommentare e configurare quando il servizio di autenticazione esterna sarà attivo.
 */
class JwtAuthMiddleware implements MiddlewareInterface
{
    // private string $issuer = 'https://fratellanza.eu.auth0.com/';
    // private string $audience = 'https://api.fratellanza-militare.it';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /*
        // 1. Check Authorization Header
        $authHeader = $request->getHeaderLine('Authorization');
        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => 'Missing or invalid Authorization header']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $token = substr($authHeader, 7);

        try {
            // 2. Validate Token (using firebase/php-jwt or auth0/auth0-php)
            // $decoded = JWT::decode($token, new Key($publicKey, 'RS256'));

            // 3. Attach user to request
            // $request = $request->withAttribute('user', $decoded->sub);

        } catch (\Exception $e) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => 'Invalid Token: ' . $e->getMessage()]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        */

        // Pass-through per ora (Backward Compatibility)
        return $handler->handle($request);
    }
}


