<?php

declare(strict_types=1);

namespace MCAG\InfrastrutturaIT\Persistence;

use PDO;

class PDOTaskflowRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM taskflow_tasks ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM taskflow_tasks WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function save(array $data): int
    {
        if (isset($data['id']) && $data['id']) {
            $stmt = $this->pdo->prepare("UPDATE taskflow_tasks SET text = :text, completed = :completed WHERE id = :id");
            $stmt->execute([
                'id' => $data['id'],
                'text' => $data['text'],
                'completed' => (int) $data['completed']
            ]);
            return (int) $data['id'];
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO taskflow_tasks (text, completed) VALUES (:text, :completed)");
            $stmt->execute([
                'text' => $data['text'],
                'completed' => isset($data['completed']) ? (int) $data['completed'] : 0
            ]);
            return (int) $this->pdo->lastInsertId();
        }
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM taskflow_tasks WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function clearCompleted(): bool
    {
        return $this->pdo->exec("DELETE FROM taskflow_tasks WHERE completed = 1") !== false;
    }
}
