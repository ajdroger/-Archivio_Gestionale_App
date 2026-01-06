<?php
declare(strict_types=1);

namespace FratellanzaMilitare\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use FratellanzaMilitare\SecurityLayer\AuditTrail;
use PDO;

/**
 * API Key Authentication Middleware
 * 
 * Verifica l'API key nel header X-API-Key e controlla:
 * - Validità della chiave
 * - Scopes/permesso accesso risorsa
 * - Rate limiting specifico per chiave
 * - Scadenza
 * 
 * @package FratellanzaMilitare\Middleware
 */
class ApiKeyMiddleware implements MiddlewareInterface
{
    private const RATE_LIMIT_WINDOW = 3600; // 1 hour in seconds

    public function __construct(
        private PDO $pdo,
        private AuditTrail $audit
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $apiKey = $request->getHeaderLine('X-API-Key');

        if (empty($apiKey)) {
            return $this->jsonError('API key required. Include X-API-Key header.', 401);
        }

        // Extract prefix and validate format
        if (strlen($apiKey) < 32) {
            return $this->jsonError('Invalid API key format', 401);
        }

        $keyPrefix = substr($apiKey, 0, 8);
        $keyHash = hash('sha256', $apiKey);

        // Verify API key exists and is valid
        $stmt = $this->pdo->prepare("
            SELECT id, user_id, name, scopes, rate_limit, expires_at
            FROM api_keys
            WHERE key_hash = ?
            AND active = 1
            AND (expires_at IS NULL OR expires_at > NOW())
        ");
        $stmt->execute([$keyHash]);
        $key = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$key) {
            $this->audit->log('API_AUTH_FAILED', 'api_key', null, [
                'prefix' => $keyPrefix,
                'ip' => $request->getAttribute('ip_address')
            ]);
            return $this->jsonError('Invalid or expired API key', 403);
        }

        // Check rate limiting
        $endpoint = (string) $request->getUri()->getPath();
        if (!$this->checkRateLimit((int) $key['id'], (int) $key['rate_limit'])) {
            $this->audit->log('API_RATE_LIMIT_EXCEEDED', 'api_key', $key['id'], [
                'endpoint' => $endpoint,
                'limit' => $key['rate_limit']
            ]);

            $response = new Response();
            $response->getBody()->write(json_encode([
                'error' => 'Rate limit exceeded',
                'limit' => $key['rate_limit'],
                'window' => self::RATE_LIMIT_WINDOW . 's'
            ]));

            return $response
                ->withStatus(429)
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('X-RateLimit-Limit', (string) $key['rate_limit'])
                ->withHeader('X-RateLimit-Remaining', '0')
                ->withHeader('Retry-After', (string) self::RATE_LIMIT_WINDOW);
        }

        // Check scopes for endpoint access
        $scopes = explode(',', $key['scopes']);
        if (!$this->checkScopes($scopes, $request->getMethod(), $endpoint)) {
            $this->audit->log('API_INSUFFICIENT_SCOPES', 'api_key', $key['id'], [
                'required_scope' => $this->extractRequiredScope($request->getMethod(), $endpoint),
                'available_scopes' => $scopes
            ]);
            return $this->jsonError('Insufficient permissions for this endpoint', 403);
        }

        // Track request
        $this->trackRequest((int) $key['id'], $endpoint, $request->getMethod(), $request->getAttribute('ip_address'));

        // Update last_used_at
        $this->pdo->prepare("UPDATE api_keys SET last_used_at = NOW() WHERE id = ?")
            ->execute([$key['id']]);

        // Add API context to request for controllers
        $request = $request->withAttribute('api_key_id', $key['id']);
        $request = $request->withAttribute('api_user_id', $key['user_id']);
        $request = $request->withAttribute('api_scopes', $scopes);
        $request = $request->withAttribute('api_key_name', $key['name']);
        $request = $request->withAttribute('is_api_request', true);

        $this->audit->log('API_REQUEST_SUCCESS', 'api_key', $key['id'], [
            'endpoint' => $endpoint,
            'method' => $request->getMethod()
        ]);

        return $handler->handle($request);
    }

    private function checkRateLimit(int $apiKeyId, int $limit): bool
    {
        // Count requests in the last hour
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count
            FROM api_request_tracking
            WHERE api_key_id = ?
            AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$apiKeyId, self::RATE_LIMIT_WINDOW]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return ($result['count'] ?? 0) < $limit;
    }

    private function checkScopes(array $scopes, string $method, string $endpoint): bool
    {
        // Wildcard scope
        if (in_array('*', $scopes)) {
            return true;
        }

        $requiredScope = $this->extractRequiredScope($method, $endpoint);

        return in_array($requiredScope, $scopes);
    }

    private function extractRequiredScope(string $method, string $endpoint): string
    {
        // Extract resource from endpoint
        // Examples:
        // GET /api/v1/soci -> soci:read
        // POST /api/v1/soci -> soci:write
        // DELETE /api/v1/soci/RSSMRA80A01H501U -> soci:write

        if (preg_match('#^/api/v1/([^/]+)#', $endpoint, $matches)) {
            $resource = $matches[1];
            $action = in_array($method, ['GET', 'HEAD']) ? 'read' : 'write';
            return "$resource:$action";
        }

        return 'unknown:unknown';
    }

    private function trackRequest(int $apiKeyId, string $endpoint, string $method, ?string $ip): void
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO api_request_tracking (api_key_id, endpoint, method, ip_address, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$apiKeyId, $endpoint, $method, $ip]);
        } catch (\PDOException $e) {
            // Log error but don't fail request
            error_log("Failed to track API request: " . $e->getMessage());
        }
    }

    private function jsonError(string $message, int $code): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => $message,
            'code' => $code
        ]));

        return $response
            ->withStatus($code)
            ->withHeader('Content-Type', 'application/json');
    }
}
