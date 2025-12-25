<?php

use DI\ContainerBuilder;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\JsonFormatter;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDOSocioRepository;
use Psr\Container\ContainerInterface; // Added this use statement

// Load custom Mustache loader
require_once __DIR__ . '/../src/InfrastrutturaIT/Mustache_Loader_CascadingLoader.php';

return [
        // Logger Generale Sistema
    LoggerInterface::class => function (ContainerInterface $c) {
        $logger = new Logger('system');
        $handler = new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG);
        $handler->setFormatter(new JsonFormatter());
        $logger->pushHandler($handler);

        // Processor per aggiungere il correlate ID (RequestId) ai log
        $logger->pushProcessor(function ($record) {
            if (isset($_SERVER['HTTP_X_REQUEST_ID'])) {
                $record['extra']['request_id'] = $_SERVER['HTTP_X_REQUEST_ID'];
            }
            return $record;
        });

        // Processor per aggiungere il correlate ID (RequestId) ai log di audit
        $logger->pushProcessor(function ($record) {
            if (isset($_SERVER['HTTP_X_REQUEST_ID'])) {
                $record['extra']['request_id'] = $_SERVER['HTTP_X_REQUEST_ID'];
            }
            return $record;
        });

        return $logger;
    },

    // Canale Audit (Sicurezza e Tracciabilità) con Pseudonimizzazione
    'audit_logger' => function () {
        $logger = new Logger('audit');
        $handler = new StreamHandler(__DIR__ . '/../logs/audit_trail.log', Logger::INFO);
        $handler->setFormatter(new JsonFormatter());

        // Processore per mascherare dati sensibili nei log (Codice Fiscale, etc.)
        $logger->pushProcessor(function ($record) {
            $maskSensitive = function ($data) use (&$maskSensitive) {
                if (is_array($data)) {
                    foreach ($data as $k => $v) {
                        $data[$k] = $maskSensitive($v);
                    }
                } elseif (is_string($data)) {
                    // Pattern Codice Fiscale
                    if (preg_match('/^[A-Z0-9]{16}$/i', $data)) {
                        return substr($data, 0, 4) . '********' . substr($data, -4);
                    }
                    // Pattern Email basic mask
                    if (str_contains($data, '@')) {
                        $parts = explode('@', $data);
                        return substr($parts[0], 0, 1) . '***@' . $parts[1];
                    }
                }
                return $data;
            };

            $record->extra['context_masked'] = $maskSensitive($record->context);
            return $record;
        });

        $logger->pushHandler($handler);
        return $logger;
    },

    // Validation Service
    \FratellanzaMilitare\Service\ValidationService::class => function () {
        return new \FratellanzaMilitare\Service\ValidationService();
    },

    // Backup Service
    \FratellanzaMilitare\Service\BackupService::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Service\BackupService(
            __DIR__ . '/../database.sqlite',
            __DIR__ . '/../storage/backups',
            $c->get(LoggerInterface::class),
            14 // Maggiore retention per mission-critical
        );
    },

    \FratellanzaMilitare\Debug\ResilienceMonitor::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Debug\ResilienceMonitor(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class),
            __DIR__ . '/../storage'
        );
    },

    // Email Service
    // Email Service
    \FratellanzaMilitare\Service\EmailServiceInterface::class => function (ContainerInterface $c) {
        $config = [
            'host' => $_ENV['SMTP_HOST'] ?? 'smtp.example.com',
            'username' => $_ENV['SMTP_USER'] ?? 'user@example.com',
            'password' => $_ENV['SMTP_PASS'] ?? 'secret',
            'port' => $_ENV['SMTP_PORT'] ?? 587
        ];
        return new \FratellanzaMilitare\Service\SmtpEmailService($c->get(LoggerInterface::class), $config);
    },

    // Connessione al Database
    PDO::class => function () {
        return DatabaseConnection::getConnection();
    },

        // Repository
    PDOSocioRepository::class => function (PDO $pdo) {
        return new PDOSocioRepository($pdo);
    },
    // Binding Interfaccia -> Implementazione
    \FratellanzaMilitare\GestioneSoci\SocioRepository::class => \DI\get(PDOSocioRepository::class),

    // Motore    // Template Engine (Mustache) - Support for subdirectories
    Mustache_Engine::class => function () {
        $templatePaths = [
            __DIR__ . '/../templates',           // Root templates (backwards compatibility)
            __DIR__ . '/../templates/auth',      // Authentication templates
            __DIR__ . '/../templates/soci',      // Soci management templates
            __DIR__ . '/../templates/admin',     // Admin/Dashboard templates
            __DIR__ . '/../templates/layout',    // Layout components
            __DIR__ . '/../templates/errors',    // Error pages
        ];

        return new Mustache_Engine([
            'loader' => new Mustache_Loader_CascadingLoader($templatePaths),
            'partials_loader' => new Mustache_Loader_CascadingLoader($templatePaths),
            'escape' => function ($value) {
                return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
            },
        ]);
    },

    // Services
    \FratellanzaMilitare\Service\PdfGenerationService::class => function () {
        return new \FratellanzaMilitare\Service\PdfGenerationService();
    },

    \FratellanzaMilitare\Service\RegistrationService::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Service\RegistrationService(
            $c->get(\FratellanzaMilitare\GestioneSoci\SocioRepository::class),
            $c->get(\FratellanzaMilitare\Service\ValidationService::class),
            $c->get(\FratellanzaMilitare\Service\PdfGenerationService::class),
            $c->get(\FratellanzaMilitare\Service\EmailServiceInterface::class),
            $c->get(LoggerInterface::class)
        );
    },

    // Controller
    \FratellanzaMilitare\Controller\SocioController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\SocioController(
            $c->get(Mustache_Engine::class),
            $c->get(PDOSocioRepository::class),
            $c->get('audit_logger'),
            $c->get(\FratellanzaMilitare\Service\ValidationService::class),
            $c->get(\FratellanzaMilitare\Service\RegistrationService::class)
        );
    },

    \FratellanzaMilitare\Controller\LoginController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\LoginController($c->get(Mustache_Engine::class));
    },

    \FratellanzaMilitare\Controller\SettingsController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\SettingsController($c->get(Mustache_Engine::class));
    },

    \FratellanzaMilitare\Controller\HomeController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\HomeController(
            $c->get(Mustache_Engine::class),
            $c->get(\FratellanzaMilitare\GestioneSoci\SocioRepository::class)
        );
    },

    // Legacy DevToolsController (kept for backwards compatibility)
    \FratellanzaMilitare\Controller\DevToolsController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\DevToolsController(
            $c->get(Mustache_Engine::class),
            $c->get(\FratellanzaMilitare\Debug\ResilienceMonitor::class)
        );
    },

    // NEW: Split DevTools Controllers (SOLID Refactor)
    \FratellanzaMilitare\Controller\DevTools\DevToolsDashboardController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\DevTools\DevToolsDashboardController(
            $c->get(Mustache_Engine::class),
            $c->get(\FratellanzaMilitare\Debug\ResilienceMonitor::class)
        );
    },

    \FratellanzaMilitare\Controller\DevTools\DevToolsFileSystemController::class => function () {
        return new \FratellanzaMilitare\Controller\DevTools\DevToolsFileSystemController();
    },

    \FratellanzaMilitare\Controller\DevTools\DevToolsDatabaseController::class => function (ContainerInterface $c) {
        return new \FratellanzaMilitare\Controller\DevTools\DevToolsDatabaseController(
            $c->get(Mustache_Engine::class)
        );
    },

    \FratellanzaMilitare\Controller\DevTools\DevToolsSecurityController::class => function () {
        return new \FratellanzaMilitare\Controller\DevTools\DevToolsSecurityController();
    },

    \FratellanzaMilitare\Controller\DevTools\DevToolsScriptController::class => function () {
        return new \FratellanzaMilitare\Controller\DevTools\DevToolsScriptController();
    },
];
