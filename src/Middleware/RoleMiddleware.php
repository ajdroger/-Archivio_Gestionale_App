<?php

namespace FratellanzaMilitare\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class RoleMiddleware implements MiddlewareInterface
{
    private array $allowedRoles;

    public function __construct(array $allowedRoles)
    {
        $this->allowedRoles = $allowedRoles;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $userRole = $_SESSION['user_role'] ?? '';

        // Admin has access to everything
        if ($userRole === 'admin') {
            return $handler->handle($request);
        }

        if (!in_array($userRole, $this->allowedRoles)) {
            $response = new SlimResponse();

            // JSON for AJAX
            if ($request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
                $response->getBody()->write(json_encode([
                    'error' => true,
                    'message' => 'Accesso negato: Ruolo non autorizzato (' . $userRole . ').',
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            // Redirect for standard request
            // If user is logged in but unauthorized -> Dashboard with error? Or separate error page?
            // For now, redirect to Flash error logic or simple 403.
            // Let's us 403 page if possible, or redirect home.
            // Simplified: Redirect to Dashboard.
            return $response->withHeader('Location', '/fratellanza-militare-archivio/public/')->withStatus(302);
        }

        return $handler->handle($request);
    }
}
