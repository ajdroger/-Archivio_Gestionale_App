<?php

namespace FratellanzaMilitare\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;
use Psr\Log\LoggerInterface;

class RateLimitMiddleware
{
    private string $storageDir;
    private int $limit;
    private int $window;
    private ?LoggerInterface $logger;

    public function __construct(int $limit = 60, int $window = 60, ?LoggerInterface $logger = null)
    {
        $this->limit = $limit; // Requests
        $this->window = $window; // Seconds
        $this->logger = $logger;
        $this->storageDir = sys_get_temp_dir() . '/fm_ratelimit';

        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0777, true);
        }
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $ip = $this->getClientIp($request);
        // Clean old files occasionally (1 in 100 chance)
        if (rand(1, 100) === 1) {
            $this->gc();
        }

        $key = md5($ip . '_' . $request->getUri()->getPath()); // Rate limit per IP per Path
        $file = $this->storageDir . '/' . $key;

        $current = 0;
        $expires = time() + $this->window;

        if (file_exists($file)) {
            $content = @file_get_contents($file);
            if ($content) {
                $data = json_decode($content, true);
                // Check if valid JSON
                if (isset($data['expires']) && $data['expires'] > time()) {
                    $current = $data['hits'];
                    $expires = $data['expires'];
                } else {
                    // Window expired, reset
                    $current = 0;
                    // Reset expiry to now + window
                    $expires = time() + $this->window;
                }
            }
        }

        if ($current >= $this->limit) {
            if ($this->logger) {
                $this->logger->warning("Rate Limit Exceeded", [
                    'ip' => $ip,
                    'path' => $request->getUri()->getPath(),
                    'limit' => $this->limit
                ]);
            }

            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'error' => 'Too Many Requests',
                'message' => 'Hai superato il numero massimo di richieste. Riprova più tardi.'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(429)
                ->withHeader('Retry-After', (string) ($expires - time()));
        }

        // Increment
        $current++;
        file_put_contents($file, json_encode(['hits' => $current, 'expires' => $expires]));

        return $handler->handle($request);
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
