<?php

namespace MCAG\Middleware;

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

        // Normalize for check
        $checkRole = strtolower($userRole);
        $allowedLower = array_map('strtolower', $this->allowedRoles);

        if (!in_array($checkRole, $allowedLower)) {
            $msg = "Role Denial: Session Role uses '$userRole' (Normalized: '$checkRole'). Allowed: " . json_encode($this->allowedRoles);
            error_log($msg); // Use standard error log as custom file failed

            $response = new SlimResponse();

            // JSON for AJAX or HTMX
            if ($request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest' || $request->getHeaderLine('HX-Request') === 'true') {
                $response->getBody()->write(json_encode([
                    'error' => "Accesso negato. Ruolo rilevato: '$userRole'. Contatta l'amministratore.",
                    'debug_role' => $userRole
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            // Redirect for standard request
            return $response->withHeader('Location', '/MCAG_Militare-Civile-Archivio-Gestionale/public/')->withStatus(302);
        }

        return $handler->handle($request);
    }
}


