<?php

/**
 * Script per verificare il funzionamento dell'Event Bus (v5.0 Architecture)
 * Usage: php bin/maintenance/test_event_bus.php
 */

require __DIR__ . '/../../vendor/autoload.php';

use DI\ContainerBuilder;
use FratellanzaMilitare\Event\EventBusInterface;
use FratellanzaMilitare\Event\Events\SocioCreatedEvent;
use Psr\Log\LoggerInterface;

// 1. Bootstrap Container
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../../config/definitions/services.php');
// $containerBuilder->addDefinitions(__DIR__ . '/../../config/definitions/database.php');
// Mock settings if needed or load real ones
$containerBuilder->addDefinitions([
    LoggerInterface::class => function () {
        // Simple stdout logger for testing
        return new class implements LoggerInterface {
            public function emergency($message, array $context = []): void
            {
                echo "[EMERGENCY] $message\n";
            }
            public function alert($message, array $context = []): void
            {
                echo "[ALERT] $message\n";
            }
            public function critical($message, array $context = []): void
            {
                echo "[CRITICAL] $message\n";
            }
            public function error($message, array $context = []): void
            {
                echo "[ERROR] $message\n";
            }
            public function warning($message, array $context = []): void
            {
                echo "[WARNING] $message\n";
            }
            public function notice($message, array $context = []): void
            {
                echo "[NOTICE] $message\n";
            }
            public function info($message, array $context = []): void
            {
                echo "[INFO] $message " . json_encode($context) . "\n";
            }
            public function debug($message, array $context = []): void
            {
                echo "[DEBUG] $message\n";
            }
            public function log($level, $message, array $context = []): void
            {
                echo "[$level] $message\n";
            }
        };
    }
]);

$container = $containerBuilder->build();

// 2. Get Event Bus
echo "--- Testing Event Bus (Architecture v5.0) ---\n";
try {
    $eventBus = $container->get(EventBusInterface::class);
    echo "✅ EventBus retrieved from Container.\n";

    // 3. Dispatch Event
    echo "Dispatching SocioCreatedEvent...\n";
    $event = new SocioCreatedEvent(
        999,
        'TESTCF12345',
        'Mario',
        'Rossi',
        'CLI_TEST_USER'
    );

    $eventBus->dispatch($event);
    echo "✅ Event dispatched.\n";
    echo "Check output above for [INFO] log.\n";

} catch (\Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}
