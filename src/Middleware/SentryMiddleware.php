<?php
declare(strict_types=1);

namespace MCAG\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sentry\State\Scope;
use function Sentry\captureException;
use function Sentry\configureScope;

class SentryMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Enrich Sentry context
        configureScope(function (Scope $scope) use ($request): void {
            $scope->setContext('request', [
                'url' => (string) $request->getUri(),
                'method' => $request->getMethod(),
                'query_string' => $request->getUri()->getQuery(),
                'ip' => $request->getAttribute('ip_address'),
                'user_agent' => $request->getHeaderLine('User-Agent'),
            ]);

            // User context if authenticated
            if ($userId = $request->getAttribute('user_id')) {
                $scope->setUser([
                    'id' => $userId,
                    'username' => $request->getAttribute('username'),
                    'ip_address' => $request->getAttribute('ip_address'),
                ]);
            }

            // API request context
            if ($request->getAttribute('is_api_request')) {
                $scope->setTag('api_key_name', $request->getAttribute('api_key_name'));
                $scope->setTag('request_type', 'api');
            } else {
                $scope->setTag('request_type', 'web');
            }
        });

        try {
            $response = $handler->handle($request);

            // Capture slow requests
            $startTime = $request->getAttribute('request_start_time');
            if ($startTime) {
                $duration = (microtime(true) - $startTime) * 1000;
                if ($duration > 1000) { // >1 second
                    // Note: Severity class might change in Sentry 4.0. Verified after composer update.
                    \Sentry\captureMessage("Slow request: {$request->getUri()->getPath()} ({$duration}ms)");
                }
            }

            return $response;
        } catch (\Throwable $e) {
            captureException($e);
            throw $e; // Re-throw for normal error handling
        }
    }
}


