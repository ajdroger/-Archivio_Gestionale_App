<?php

declare(strict_types=1);

namespace FratellanzaMilitare\Service;

use FratellanzaMilitare\Jobs\JobInterface;
use PDO;
use Exception;

/**
 * Queue Service
 * 
 * Gestisce la queue dei background jobs con retry logic e scheduling.
 */
/**
 * Servizio per la gestione di Code di lavorazione (Database-backed).
 * 
 * Permette di accodare job (push_jobs), processarli (pop), gestirne il completamento
 * o il fallimento (failed_jobs) e supporta delay e retry automatici.
 */
class QueueService
{
    private PDO $pdo;
    private int $maxRetries = 3;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Aggiungi un job alla coda.
     * 
     * @param JobInterface $job Oggetto che implementa la logica del job
     * @param int $delay Ritardo in secondi prima che il job sia disponibile
     * @return bool
     */
    public function push(JobInterface $job, int $delay = 0): bool
    {
        try {
            $payload = json_encode($job->getPayload());
            $availableAt = time() + $delay;
            $createdAt = time();

            $stmt = $this->pdo->prepare("
                INSERT INTO jobs (queue, payload, attempts, available_at, created_at)
                VALUES (:queue, :payload, 0, :available_at, :created_at)
            ");

            return $stmt->execute([
                'queue' => $job->getQueue(),
                'payload' => $payload,
                'available_at' => $availableAt,
                'created_at' => $createdAt,
            ]);
        } catch (Exception $e) {
            error_log("Queue push error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Preleva il prossimo job disponibile dalla coda ed esegue lock (SELECT FOR UPDATE).
     * 
     * Implementa un meccanismo transazionale per garantire che un job sia elaborato
     * da un solo worker alla volta.
     * 
     * @param string $queue Nome della coda
     * @return array|null Dati del job o null se vuota
     */
    public function pop(string $queue = 'default'): ?array
    {
        try {
            $this->pdo->beginTransaction();

            // Find next available job
            $stmt = $this->pdo->prepare("
                SELECT * FROM jobs
                WHERE queue = :queue
                AND reserved_at IS NULL
                AND available_at <= :now
                ORDER BY id ASC
                LIMIT 1
                FOR UPDATE
            ");

            $stmt->execute([
                'queue' => $queue,
                'now' => time(),
            ]);

            $job = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$job) {
                $this->pdo->commit();
                return null;
            }

            // Reserve the job
            $updateStmt = $this->pdo->prepare("
                UPDATE jobs
                SET reserved_at = :reserved_at,
                    attempts = attempts + 1
                WHERE id = :id
            ");

            $updateStmt->execute([
                'reserved_at' => time(),
                'id' => $job['id'],
            ]);

            $this->pdo->commit();

            $job['payload'] = json_decode($job['payload'], true);
            return $job;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Queue pop error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Marca un job come completato e lo rimuove dalla tabella jobs.
     */
    public function complete(int $jobId): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM jobs WHERE id = :id");
            return $stmt->execute(['id' => $jobId]);
        } catch (Exception $e) {
            error_log("Queue complete error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Rilascia un job nella coda (es. dopo un fallimento temporaneo) con un delay.
     */
    public function release(int $jobId, int $delay = 60): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE jobs
                SET reserved_at = NULL,
                    available_at = :available_at
                WHERE id = :id
            ");

            return $stmt->execute([
                'id' => $jobId,
                'available_at' => time() + $delay,
            ]);
        } catch (Exception $e) {
            error_log("Queue release error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marca un job come fallito definitivamente.
     * 
     * Sposta il job nella tabella 'failed_jobs' con lo stack trace dell'errore
     * per successiva ispezione manuale.
     */
    public function fail(int $jobId, Exception $exception): bool
    {
        try {
            $this->pdo->beginTransaction();

            // Get job data
            $stmt = $this->pdo->prepare("SELECT * FROM jobs WHERE id = :id");
            $stmt->execute(['id' => $jobId]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($job) {
                // Move to failed_jobs
                $failStmt = $this->pdo->prepare("
                    INSERT INTO failed_jobs (queue, payload, exception, failed_at)
                    VALUES (:queue, :payload, :exception, :failed_at)
                ");

                $failStmt->execute([
                    'queue' => $job['queue'],
                    'payload' => $job['payload'],
                    'exception' => $exception->getMessage() . "\n" . $exception->getTraceAsString(),
                    'failed_at' => time(),
                ]);
            }

            // Delete from jobs
            $deleteStmt = $this->pdo->prepare("DELETE FROM jobs WHERE id = :id");
            $deleteStmt->execute(['id' => $jobId]);

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Queue fail error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get queue statistics
     */
    public function getStats(string $queue = 'default'): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN reserved_at IS NULL THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN reserved_at IS NOT NULL THEN 1 ELSE 0 END) as processing
                FROM jobs
                WHERE queue = :queue
            ");

            $stmt->execute(['queue' => $queue]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Failed jobs count
            $failedStmt = $this->pdo->prepare("SELECT COUNT(*) FROM failed_jobs WHERE queue = :queue");
            $failedStmt->execute(['queue' => $queue]);
            $stats['failed'] = $failedStmt->fetchColumn();

            return $stats;

        } catch (Exception $e) {
            return [
                'total' => 0,
                'pending' => 0,
                'processing' => 0,
                'failed' => 0,
            ];
        }
    }
}
