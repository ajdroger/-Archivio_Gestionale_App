<?php

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// 1. Load Environment Variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// --- DEBUG ROUTING V3 (TOP LEVEL) ---
$logData = sprintf(
    "[%s] Method: %s | URI: %s | Script: %s | HTTPS: %s | Host: %s\n",
    date('Y-m-d H:i:s'),
    $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
    $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
    $_SERVER['SCRIPT_NAME'] ?? 'UNKNOWN',
    $_SERVER['HTTPS'] ?? 'off',
    $_SERVER['HTTP_HOST'] ?? 'UNKNOWN'
);
file_put_contents(__DIR__ . '/../logs/route_debug_v3.log', $logData, FILE_APPEND);
// ------------------------------------

// 1.5 Secure Session Configuration
ini_set('memory_limit', '256M');
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

// 1.7 Force HTTPS in production (Use 307 to preserve POST data)
if (($_ENV['APP_ENV'] ?? 'production') === 'production') {
    // Skip if on localhost to avoid issues with Ampps self-signed certs or simple HTTP dev
    $isLocal = in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1']);

    if (!$isLocal && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on')) {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 307);
        exit;
    }
}

// 2. Build Container
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../config/container.php');
$container = $containerBuilder->build();

// Initialize AuditTrail Bridge for Singleton compatibility
$auditTrail = \FratellanzaMilitare\SecurityLayer\AuditTrail::getInstance();
$auditTrail->setLogger($container->get('audit_logger'));
$auditTrail->setPdo($container->get(PDO::class));

// 3. Create App
AppFactory::setContainer($container);
$app = AppFactory::create();
$app->setBasePath('/fratellanza-militare-archivio/public');

// 4. Register Middleware
$middleware = require __DIR__ . '/../config/middleware.php';
$middleware($app);

// 5. Register Routes
$routes = require __DIR__ . '/../config/routes.php';
$routes($app);

// 6. Run
$app->run();
