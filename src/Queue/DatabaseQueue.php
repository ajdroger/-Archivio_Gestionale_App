<?php

namespace FratellanzaMilitare\Queue;

use FratellanzaMilitare\Queue\Job\JobInterface;

class DatabaseQueue implements QueueInterface
{
    private \PDO $pdo;
    private string $table;

    public function __construct(\PDO $pdo, string $table = 'jobs')
    {
        $this->pdo = $pdo;
        $this->table = $table;
    }

    public function push(JobInterface $job): void
    {
        $payload = serialize($job);
        $now = time();
        $stmt = $this->pdo->prepare("INSERT INTO {$this->table} (queue, payload, created_at, available_at) VALUES (?, ?, ?, ?)");
        $stmt->execute(['default', $payload, $now, $now]);
    }

    public function pop(): ?JobInterface
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare("
                SELECT id, payload FROM {$this->table} 
                WHERE reserved_at IS NULL AND available_at <= ? 
                ORDER BY id ASC 
                LIMIT 1 
                FOR UPDATE
            ");
            $stmt->execute([time()]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$row) {
                $this->pdo->rollBack();
                return null;
            }

            // Mark as reserved
            $updateStmt = $this->pdo->prepare("UPDATE {$this->table} SET reserved_at = ? WHERE id = ?");
            $updateStmt->execute([time(), $row['id']]);

            $this->pdo->commit();

            $job = unserialize($row['payload']);

            // Delete after successful processing (for simplicity; production would mark as completed)
            $deleteStmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
            $deleteStmt->execute([$row['id']]);

            return $job;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
