<?php

/**
 * Vercel Serverless Entry Point
 * 
 * This file serves as the entry point for Vercel's serverless PHP runtime.
 * It bootstraps the Slim Framework application and handles all incoming requests.
 */

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

// Load Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// 1. Load Environment Variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// 2. Secure Session Configuration
ini_set('memory_limit', '256M');
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

// 3. Build Container
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../config/container.php');
$container = $containerBuilder->build();

// Initialize AuditTrail Bridge for Singleton compatibility
$auditTrail = \FratellanzaMilitare\SecurityLayer\AuditTrail::getInstance();
$auditTrail->setLogger($container->get('audit_logger'));
$auditTrail->setPdo($container->get(PDO::class));

// 4. Create App
AppFactory::setContainer($container);
$app = AppFactory::create();

// Vercel uses root path, no base path needed
// $app->setBasePath('');

// 5. Register Middleware
$middleware = require __DIR__ . '/../config/middleware.php';
$middleware($app);

// 6. Register Routes
$routes = require __DIR__ . '/../config/routes.php';
$routes($app);

// 7. Run
$app->run();
