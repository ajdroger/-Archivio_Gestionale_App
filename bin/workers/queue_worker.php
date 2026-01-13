<?php

declare(strict_types=1);

/**
 * Queue Worker - Processa job dalla queue
 * 
 * Usage:
 *   php bin/workers/queue_worker.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use DI\ContainerBuilder;
use Dotenv\Dotenv;
use FratellanzaMilitare\Queue\QueueInterface;
use FratellanzaMilitare\Queue\Job\JobInterface;
use Psr\Container\ContainerInterface;

// Load environment
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Build Container
$containerBuilder = new ContainerBuilder();
if (($_ENV['APP_ENV'] ?? 'production') === 'production') {
    $containerBuilder->enableCompilation(__DIR__ . '/../../var/cache');
}

// Load definitions from central configuration
$definitions = require __DIR__ . '/../../config/container.php';
foreach ($definitions as $def) {
    $containerBuilder->addDefinitions($def);
}

/** @var ContainerInterface $container */
$container = $containerBuilder->build();

// Get Queue
/** @var QueueInterface $queue */
$queue = $container->get(QueueInterface::class);

echo "🚀 Queue Worker started (Standard Queue)\n";
echo "Press Ctrl+C to stop\n\n";

$processed = 0;

while (true) {
    try {
        // Pop next job
        $job = $queue->pop();

        if ($job === null) {
            usleep(500000); // 500ms sleep
            continue;
        }

        echo "[" . date('H:i:s') . "] Processing Job: " . get_class($job) . "\n";

        if ($job instanceof JobInterface) {
            // Execute Job passing Container for DI resolution
            $job->handle($container);
            $processed++;
            echo "  ✓ Completed.\n\n";
        } else {
            echo "  ✗ Error: Job does not implement JobInterface\n";
        }

    } catch (\Throwable $e) {
        echo "  ✗ Worker Critical Error: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n\n";
        sleep(2);
    }
}
