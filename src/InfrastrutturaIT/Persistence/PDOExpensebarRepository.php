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
        $this->createTableIfNotExists();
    }

    private function createTableIfNotExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS expensebar_expenses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            description VARCHAR(255) NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            category VARCHAR(50) NOT NULL,
            date DATE NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->pdo->exec($sql);
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

    public function findByMonth(int $month, int $year): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM expensebar_expenses 
            WHERE MONTH(date) = :month 
            AND YEAR(date) = :year 
            ORDER BY date DESC, created_at DESC
        ");
        $stmt->execute(['month' => $month, 'year' => $year]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategoryStats(int $month, int $year): array
    {
        $stmt = $this->pdo->prepare("
            SELECT category, SUM(amount) as total 
            FROM expensebar_expenses 
            WHERE MONTH(date) = :month 
            AND YEAR(date) = :year 
            GROUP BY category 
            ORDER BY total DESC
        ");
        $stmt->execute(['month' => $month, 'year' => $year]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMonthlyTotals(int $year): array
    {
        $stmt = $this->pdo->prepare("
            SELECT MONTH(date) as month, SUM(amount) as total 
            FROM expensebar_expenses 
            WHERE YEAR(date) = :year 
            GROUP BY month 
            ORDER BY month ASC
        ");
        $stmt->execute(['year' => $year]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
