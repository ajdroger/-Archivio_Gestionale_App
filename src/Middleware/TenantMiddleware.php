<?php

namespace MCAG\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use MCAG\InfrastrutturaIT\Persistence\DatabaseConnection;
use PDO;

class TenantMiddleware
{
    /**
     * Middleare che intercetta la richiesta, identifica il tenant dal sottodominio
     * e switcha la connessione al database appropriato.
     */
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $host = $request->getUri()->getHost();

        // Skip for IP access or localhost in development if not configured
        if ($this->isLocalOrIp($host)) {
            // In local env, we might want to default to 'mcag' or skip logic
            // For strict SaaS simulation, we default to the env DB or a default tenant
            return $handler->handle($request);
        }

        // Extract subdomain (e.g. 'milano' from 'milano.mcag.com')
        $parts = explode('.', $host);
        $subdomain = $parts[0];

        // "www" or root domain -> Landing Page context (do nothing, use default DB)
        if ($subdomain === 'www' || $subdomain === 'mcag-system' || count($parts) < 2) {
            return $handler->handle($request);
        }

        // 1. Connect to Core DB to Resolve Tenant
        $pdo = DatabaseConnection::getConnection();

        // This query assumes we have created the 'tenants' table in the core DB.
        // If the table doesn't exist yet, this will fail.
        // We wrap in try-catch to allow migration bootstrapping.
        try {
            $stmt = $pdo->prepare("SELECT db_name, status FROM tenants WHERE subdomain = :subdomain LIMIT 1");
            $stmt->execute(['subdomain' => $subdomain]);
            $tenant = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tenant) {
                // Tenant Not Found -> 404 or Redirect
                $response = new \Slim\Psr7\Response();
                $response->getBody()->write("Tenant '$subdomain' not found.");
                return $response->withStatus(404);
            }

            if ($tenant['status'] !== 'active') {
                // Tenant Suspended
                $response = new \Slim\Psr7\Response();
                $response->getBody()->write("This workspace is currently suspended. Please contact support.");
                return $response->withStatus(403);
            }

            // 2. Switch Context
            DatabaseConnection::connectToTenant($tenant['db_name']);

            // Optional: Store tenant info in request attribute for Controllers
            $request = $request->withAttribute('tenant_subdomain', $subdomain);
            $request = $request->withAttribute('tenant_db', $tenant['db_name']);

        } catch (\PDOException $e) {
            // Log error and fallback (or fail secure)
            error_log("Tenant Resolution Failed: " . $e->getMessage());
            // If table doesn't exist, we proceed (might be initial setup)
        }

        return $handler->handle($request);
    }

    private function isLocalOrIp(string $host): bool
    {
        return $host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP);
    }
}
