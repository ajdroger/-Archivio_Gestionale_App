<?php

namespace MCAG\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        // Session check
        if (!isset($_SESSION['user_id'])) {
            $routeContext = \Slim\Routing\RouteContext::fromRequest($request);
            $route = $routeContext->getRoute();

            // If no route matched (404), let the app handle it (or let ErrorMiddleware catch it)
            if (empty($route)) {
                return $handler->handle($request);
            }

            $routeName = $route->getName();
            $publicRoutes = [
                'login',
                'login_verify',
                'login_2fa',
                'login_2fa_verify',
                'logout',
                'register',
                'register_verify',
                'graphql_api',
                'graphql_endpoint', // Keep both just in case
                'api_docs',
                'api_docs_json',
                'demo_launch',
                'demo_request_submit'
            ];

            // Allow public routes
            if (in_array($routeName, $publicRoutes)) {
                return $handler->handle($request);
            }

            // Redirect to login
            $response = new SlimResponse();
            $routeParser = $routeContext->getRouteParser();
            $loginUrl = $routeParser->urlFor('login');

            return $response->withHeader('Location', $loginUrl)->withStatus(302);
        }

        return $handler->handle($request);
    }
}


