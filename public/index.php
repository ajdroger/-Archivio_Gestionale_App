<?php

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// 1. Load Environment Variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// 1.5 Secure Session Configuration
ini_set('memory_limit', '256M');
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

// 1.7 Force HTTPS in production
if (($_ENV['APP_ENV'] ?? 'production') === 'production') {
    if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
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
