<?php

use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Log\LoggerInterface;

return function (App $app) {
    $container = $app->getContainer();
    $logger = $container->get(LoggerInterface::class);

    // 0. Request ID (Correlation ID) - Primo della lista per tracciabilità totale
    $app->add(new \FratellanzaMilitare\Middleware\RequestIdMiddleware());

    // 1. Configurazione Sessioni Sicura (Mission-critical)
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', 'Lax'); // Lax avoids session loss on localhost redirects/ajax
        ini_set('session.cookie_path', '/');
        ini_set('session.gc_maxlifetime', 3600); // 1 ora di validità

        $secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        ini_set('session.cookie_secure', $secure ? 1 : 0);
        ini_set('session.use_only_cookies', 1);
        session_start();
    }

    // CSRF View Injection (Automated)
    $mustache = $container->get(Mustache_Engine::class);
    $app->add(new \FratellanzaMilitare\Middleware\CsrfViewMiddleware($mustache));

    // CSRF Protection
    $responseFactory = $app->getResponseFactory();
    $guard = new \Slim\Csrf\Guard($responseFactory);
    $guard->setPersistentTokenMode(true);
    $guard->setFailureHandler(function (Request $request, RequestHandler $handler) use ($logger) {
        $logger->warning("[CSRF] Fallimento controllo per rotta: " . $request->getUri()->getPath(), [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'none'
        ]);
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode([
            'error' => true,
            'message' => 'Errore CSRF: Token non valido o sessione scaduta. Ricarica la pagina.',
            'output' => '[CSRF FAILURE]'
        ], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
    });
    $app->add($guard);

    // Security Headers
    $app->add(new \FratellanzaMilitare\Middleware\SecurityHeadersMiddleware());

    // Authentication
    $app->add(new \FratellanzaMilitare\Middleware\AuthMiddleware());

    // Rate Limiting (con Redis support)
    $redisService = $container->has(\FratellanzaMilitare\Service\RedisService::class)
        ? $container->get(\FratellanzaMilitare\Service\RedisService::class)
        : null;
    $app->add(new \FratellanzaMilitare\Middleware\RateLimitMiddleware(100, 60, $redisService, $logger));

    // Error Middleware
    $errorMiddleware = $app->addErrorMiddleware(true, true, true, $logger);
    $customErrorHandler = new \FratellanzaMilitare\Debug\GlobalExceptionHandler($logger, $mustache);
    $errorMiddleware->setDefaultErrorHandler($customErrorHandler);

    // Routing Middleware - Must be added LAST to run FIRST
    // This allows subsequent middleware (like Auth) to access RouteContext
    $app->addRoutingMiddleware();
};
