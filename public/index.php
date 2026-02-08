<?php

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Slim\Views\Mustache;

// DEBUG ROUTING
// die("DEBUG: MCAG PUBLIC INDEX REACHED");
require __DIR__ . '/../vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('html_errors', 0); // Force plain text errors for API compatibility
error_reporting(E_ALL);

// Bootstrap Sentry
if (class_exists('Sentry\SentrySdk')) {
    \Sentry\init([
        'dsn' => $_ENV['SENTRY_DSN'] ?? '',
        'environment' => $_ENV['APP_ENV'] ?? 'production',
        'release' => 'mcag-system@5.3.0',
        'traces_sample_rate' => 0.2, // 20% performance monitoring
        'profiles_sample_rate' => 0.2,
    ]);
}


// 1. Load Environment Variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// 1.5 Secure Session Configuration
session_name('FRATELLANZA_SESS_V2'); // Force fresh session to clear any corrupted data
ini_set('memory_limit', '256M');
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax'); // Relaxed for better browser compatibility (Tracking Prevention fix)
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

// 1.7 Force HTTPS in production (Use 307 to preserve POST data)
if (($_ENV['APP_ENV'] ?? 'production') === 'production') {
    // Skip if on localhost to avoid issues with Ampps self-signed certs or simple HTTP dev
    $host = explode(':', $_SERVER['HTTP_HOST'])[0];
    $isLocal = in_array($host, ['localhost', '127.0.0.1', '::1']);

    if (!$isLocal && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on')) {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 307);
        exit;
    }
}

// 2. Build Container
try {
    $containerBuilder = new ContainerBuilder();
    foreach ((require __DIR__ . '/../config/container.php') as $definitions) {
        $containerBuilder->addDefinitions($definitions);
    }
    $container = $containerBuilder->build();

    // Initialize AuditTrail Bridge for Singleton compatibility
    $auditTrail = \MCAG\SecurityLayer\AuditTrail::getInstance();
    $auditTrail->setLogger($container->get('audit_logger'));
    $auditTrail->setPdo($container->get(PDO::class));

    // 3. Create App
    AppFactory::setContainer($container);
    $app = AppFactory::create();

    // Automatic Base Path Detection
    $basePath = (function () {
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        return $scriptDir === '/' ? '' : $scriptDir;
    })();
    $app->setBasePath($basePath);

    // 4. Register Middleware
    $middleware = require __DIR__ . '/../config/middleware.php';
    $middleware($app);

    // 5. Register Routes
    $routes = require __DIR__ . '/../config/routes.php';
    $routes($app);

    //    });

    // CYBER WARFARE SUITE
    $app->post('/api/security/engage', [\MCAG\Controller\WarfareController::class, 'engageTarget']);

    // HONEYPOT TRAP (Mimics a vulnerable admin file)
    $app->any('/vulnerability/admin.php', function ($request, $response) use ($container) {
        // 1. Log the Intrusion
        $audit = $container->get(\MCAG\SecurityLayer\AuditTrail::class);
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        // Log as CRITICAL THREAT
        $audit->logEvent(
            $ip,
            'HONEYPOT_TRIGGERED',
            'Attempted access to /vulnerability/admin.php',
            'CRITICAL',
            ['method' => $request->getMethod(), 'headers' => $request->getHeaders()]
        );

        // 2. Auto-Ban (Optional? Let's keep it aggressive as requested "Global Threat Vector")
        // $firewall = new \MCAG\SecurityLayer\Arsenal\FirewallOps(__DIR__ . '/../');
        // $firewall->banIp($ip); // Direct Ban on touch

        // 3. Tarpit / Fake Response
        $response->getBody()->write("<h1>403 Forbidden</h1><p>System Integrity Verified. Incident Logged.</p><!-- TRAP TRIGGERED -->");
        return $response->withStatus(403);
    });

    // 6. Run
    $app->run();
} catch (\Throwable $e) {
    $errorMsg = "<h1>Fatal Error During Startup</h1>";
    $errorMsg .= "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    $errorMsg .= "<p><strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
    $errorMsg .= "<pre>" . $e->getTraceAsString() . "</pre>";
    // file_put_contents(__DIR__ . '/../debug_fatal_error.html', $errorMsg); // Optional logging
    echo $errorMsg;
    exit;
}

