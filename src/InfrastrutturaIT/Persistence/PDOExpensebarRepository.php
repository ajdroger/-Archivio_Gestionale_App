<?php

declare(strict_types=1);

namespace MCAG\InfrastrutturaIT\Persistence;

use PDO;

class PDOExpensebarRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM expensebar_expenses ORDER BY date DESC, created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function save(array $data): int
    {
        if (isset($data['id']) && $data['id']) {
            $stmt = $this->pdo->prepare("UPDATE expensebar_expenses SET description = :description, amount = :amount, category = :category, date = :date WHERE id = :id");
            $stmt->execute([
                'id' => $data['id'],
                'description' => $data['description'],
                'amount' => $data['amount'],
                'category' => $data['category'],
                'date' => $data['date']
            ]);
            return (int) $data['id'];
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO expensebar_expenses (description, amount, category, date) VALUES (:description, :amount, :category, :date)");
            $stmt->execute([
                'description' => $data['description'],
                'amount' => $data['amount'],
                'category' => $data['category'],
                'date' => $data['date']
            ]);
            return (int) $this->pdo->lastInsertId();
        }
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM expensebar_expenses WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
