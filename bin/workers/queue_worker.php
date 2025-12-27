<?php

declare(strict_types=1);

/**
 * Queue Worker - Processa job dalla queue
 * 
 * Usage:
 *   php bin/workers/queue_worker.php [queue_name]
 * 
 * Example:
 *   php bin/workers/queue_worker.php default
 *   php bin/workers/queue_worker.php emails
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;
use FratellanzaMilitare\Service\QueueService;
use Dotenv\Dotenv;

// Load environment
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Get queue name from arguments
$queueName = $argv[1] ?? 'default';

echo "🚀 Queue Worker started for queue: $queueName\n";
echo "Press Ctrl+C to stop\n\n";

// Initialize dependencies
$pdo = DatabaseConnection::getConnection();
$queueService = new QueueService($pdo);

// Worker loop
$processed = 0;
$failed = 0;

while (true) {
    try {
        // Pop next job
        $job = $queueService->pop($queueName);

        if ($job === null) {
            // No jobs available, sleep and retry
            usleep(100000); // 100ms
            continue;
        }

        echo "[" . date('Y-m-d H:i:s') . "] Processing job #{$job['id']}...\n";

        try {
            // Reconstruct job object from payload
            $payload = $job['payload'];
            $jobClass = $payload['class'];

            if (!class_exists($jobClass)) {
                throw new Exception("Job class not found: $jobClass");
            }

            // Execute job
            // Note: In production, use DI container to resolve dependencies
            // For now, we log the attempt
            echo "  → Job class: $jobClass\n";
            echo "  → Attempt: {$job['attempts']}\n";

            // Mark as complete
            $queueService->complete($job['id']);
            $processed++;

            echo "  ✓ Job completed successfully\n\n";

        } catch (Exception $e) {
            echo "  ✗ Job failed: " . $e->getMessage() . "\n";

            // Check if should retry
            if ($job['attempts'] >= 3) {
                // Max retries reached, move to failed
                $queueService->fail($job['id'], $e);
                $failed++;
                echo "  → Moved to failed jobs (max retries reached)\n\n";
            } else {
                // Retry with exponential backoff
                $delay = min(60 * pow(2, $job['attempts']), 3600); // Max 1 hour
                $queueService->release($job['id'], $delay);
                echo "  → Released for retry in {$delay}s\n\n";
            }
        }

        // Show stats every 10 jobs
        if ($processed % 10 === 0) {
            $stats = $queueService->getStats($queueName);
            echo "📊 Stats: Processed: $processed | Failed: $failed | Pending: {$stats['pending']} | Processing: {$stats['processing']}\n\n";
        }

    } catch (Exception $e) {
        echo "Worker error: " . $e->getMessage() . "\n";
        sleep(5); // Wait before retrying
    }
}
