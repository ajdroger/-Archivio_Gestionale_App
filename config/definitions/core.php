<?php

use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\JsonFormatter;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDOSocioRepository;
use Psr\Container\ContainerInterface;

return [
        // Logger Generale Sistema
    LoggerInterface::class => function (ContainerInterface $c) {
        $logger = new Logger('system');
        $handler = new StreamHandler(__DIR__ . '/../../logs/app.log', Logger::DEBUG);
        $handler->setFormatter(new JsonFormatter());
        $logger->pushHandler($handler);

        $processor = function ($record) {
            if (isset($_SERVER['HTTP_X_REQUEST_ID'])) {
                $record['extra']['request_id'] = $_SERVER['HTTP_X_REQUEST_ID'];
            }
            return $record;
        };
        $logger->pushProcessor($processor);
        return $logger;
    },

    // Canale Audit
    'audit_logger' => function () {
        $logger = new Logger('audit');
        $handler = new StreamHandler(__DIR__ . '/../../logs/audit_trail.log', Logger::INFO);
        $handler->setFormatter(new JsonFormatter());

        $logger->pushProcessor(function ($record) {
            $maskSensitive = function ($data) use (&$maskSensitive) {
                if (is_array($data)) {
                    foreach ($data as $k => $v) {
                        $data[$k] = $maskSensitive($v);
                    }
                } elseif (is_string($data)) {
                    if (preg_match('/^[A-Z0-9]{16}$/i', $data)) {
                        return substr($data, 0, 4) . '********' . substr($data, -4);
                    }
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

    // Connessione al Database
    PDO::class => function () {
        return DatabaseConnection::getConnection();
    },

        // Repository
    PDOSocioRepository::class => function (PDO $pdo) {
        return new PDOSocioRepository($pdo);
    },
    \FratellanzaMilitare\GestioneSoci\SocioRepository::class => \DI\get(PDOSocioRepository::class),

    // Template Engine (Mustache)
    Mustache_Engine::class => function () {
        $templatePaths = [
            __DIR__ . '/../../templates',
            __DIR__ . '/../../templates/auth',
            __DIR__ . '/../../templates/soci',
            __DIR__ . '/../../templates/admin',
            __DIR__ . '/../../templates/layout',
            __DIR__ . '/../../templates/errors',
        ];

        return new Mustache_Engine([
            'loader' => new \FratellanzaMilitare\View\CascadingLoader($templatePaths),
            'partials_loader' => new \FratellanzaMilitare\View\CascadingLoader($templatePaths),
            'escape' => function ($value) {
                return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
            },
        ]);
    },
];
