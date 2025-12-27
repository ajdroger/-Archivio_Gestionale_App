<?php

namespace FratellanzaMilitare\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;
use Psr\Log\LoggerInterface;
use FratellanzaMilitare\Service\RedisService;

/**
 * Rate Limit Middleware - Redis-backed persistente
 * 
 * Usa Redis per rate limiting distribuito e persistente.
 * Fallback graceful a file-based se Redis non disponibile.
 */
class RateLimitMiddleware
{
    private ?RedisService $redis;
    private string $storageDir;
    private int $limit;
    private int $window;
    private ?LoggerInterface $logger;
    private bool $useRedis;

    public function __construct(
        int $limit = 60,
        int $window = 60,
        ?RedisService $redis = null,
        ?LoggerInterface $logger = null
    ) {
        $this->limit = $limit; // Requests
        $this->window = $window; // Seconds
        $this->redis = $redis;
        $this->logger = $logger;
        $this->useRedis = $redis !== null && $redis->isEnabled();

        // Fallback storage for when Redis is not available
        $this->storageDir = sys_get_temp_dir() . '/fm_ratelimit';
        if (!$this->useRedis && !is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0777, true);
        }
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $ip = $this->getClientIp($request);
        $path = $request->getUri()->getPath();
        $key = 'rate_limit:' . md5($ip . '_' . $path);

        if ($this->useRedis) {
            return $this->handleRedis($key, $ip, $path, $request, $handler);
        } else {
            return $this->handleFilesystem($key, $ip, $path, $request, $handler);
        }
    }

    /**
     * Redis-based rate limiting
     */
    private function handleRedis(
        string $key,
        string $ip,
        string $path,
        Request $request,
        RequestHandler $handler
    ): Response {
        // Get current count
        $current = (int) $this->redis->get($key) ?: 0;

        if ($current >= $this->limit) {
            $ttl = $this->window;

            if ($this->logger) {
                $this->logger->warning('rate_limit.exceeded', [
                    'ip' => $ip,
                    'path' => $path,
                    'limit' => $this->limit,
                    'current' => $current,
                ]);
            }

            return $this->createRateLimitResponse($ttl);
        }

        // Increment and set expiry if first request
        $newCount = $this->redis->increment($key);
        if ($newCount === 1) {
            $this->redis->expire($key, $this->window);
        }

        $response = $handler->handle($request);

        // Add rate limit headers
        return $response
            ->withHeader('X-RateLimit-Limit', (string) $this->limit)
            ->withHeader('X-RateLimit-Remaining', (string) max(0, $this->limit - $newCount))
            ->withHeader('X-RateLimit-Reset', (string) (time() + $this->window));
    }

    /**
     * File-based rate limiting (fallback)
     */
    private function handleFilesystem(
        string $key,
        string $ip,
        string $path,
        Request $request,
        RequestHandler $handler
    ): Response {
        // Cleanup occasionally
        if (rand(1, 100) === 1) {
            $this->gc();
        }

        $file = $this->storageDir . '/' . md5($key);
        $current = 0;
        $expires = time() + $this->window;

        if (file_exists($file)) {
            $content = @file_get_contents($file);
            if ($content) {
                $data = json_decode($content, true);
                if (isset($data['expires']) && $data['expires'] > time()) {
                    $current = $data['hits'];
                    $expires = $data['expires'];
                } else {
                    $current = 0;
                    $expires = time() + $this->window;
                }
            }
        }

        if ($current >= $this->limit) {
            if ($this->logger) {
                $this->logger->warning('rate_limit.exceeded', [
                    'ip' => $ip,
                    'path' => $path,
                    'limit' => $this->limit,
                ]);
            }

            return $this->createRateLimitResponse($expires - time());
        }

        // Increment
        $current++;
        file_put_contents($file, json_encode(['hits' => $current, 'expires' => $expires]));

        return $handler->handle($request);
    }

    private function createRateLimitResponse(int $retryAfter): Response
    {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode([
            'error' => 'Too Many Requests',
            'message' => 'Hai superato il numero massimo di richieste. Riprova più tardi.'
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(429)
            ->withHeader('Retry-After', (string) $retryAfter)
            ->withHeader('X-RateLimit-Limit', (string) $this->limit)
            ->withHeader('X-RateLimit-Remaining', '0');
    }

    private function getClientIp(Request $request): string
    {
        $serverParams = $request->getServerParams();
        return $serverParams['HTTP_CLIENT_IP']
            ?? $serverParams['HTTP_X_FORWARDED_FOR']
            ?? $serverParams['REMOTE_ADDR']
            ?? 'UNKNOWN';
    }

    private function gc(): void
    {
        foreach (glob($this->storageDir . '/*') as $file) {
            if (is_file($file) && time() - filemtime($file) > $this->window) {
                @unlink($file);
            }
        }
    }
}
