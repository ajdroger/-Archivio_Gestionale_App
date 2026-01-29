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
        // Expenses Table
        $sql = "CREATE TABLE IF NOT EXISTS expensebar_expenses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            description VARCHAR(255) NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            category VARCHAR(50) NOT NULL,
            date DATE NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->pdo->exec($sql);

        // Budgets Table (Genius System)
        $sqlBudgets = "CREATE TABLE IF NOT EXISTS expensebar_budgets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category VARCHAR(50) NOT NULL,
            amount_limit DECIMAL(10, 2) NOT NULL,
            month INT NOT NULL,
            year INT NOT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_budget (category, month, year)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->pdo->exec($sqlBudgets);
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

    public function saveBudget(string $category, float $amount, int $month, int $year): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO expensebar_budgets (category, amount_limit, month, year) 
            VALUES (:category, :amount, :month, :year)
            ON DUPLICATE KEY UPDATE amount_limit = :amount_update
        ");
        $stmt->execute([
            'category' => $category,
            'amount' => $amount,
            'month' => $month,
            'year' => $year,
            'amount_update' => $amount
        ]);
    }

    public function getBudgets(int $month, int $year): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM expensebar_budgets WHERE month = :month AND year = :year");
        $stmt->execute(['month' => $month, 'year' => $year]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $keyed = [];
        foreach ($results as $row) {
            $keyed[$row['category']] = (float) $row['amount_limit'];
        }
        return $keyed;
    }

    public function getBudgetStatus(int $month, int $year): array
    {
        // Get actual spending
        $stmt = $this->pdo->prepare("
            SELECT category, SUM(amount) as spent 
            FROM expensebar_expenses 
            WHERE MONTH(date) = :month AND YEAR(date) = :year 
            GROUP BY category
        ");
        $stmt->execute(['month' => $month, 'year' => $year]);
        $spending = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [category => spent]

        // Get limits
        $limits = $this->getBudgets($month, $year);

        // Define all known categories (Genius System defaults)
        $allCategories = [
            'Food & Dining',
            'Transportation',
            'Shopping',
            'Entertainment',
            'Bills & Utilities',
            'Healthcare',
            'Travel',
            'Education',
            'Other'
        ];

        $status = [];
        foreach ($allCategories as $cat) {
            $limit = $limits[$cat] ?? 0.0; // Default limit 0 means "Unset"
            $spent = (float) ($spending[$cat] ?? 0.0);

            // Calculate health
            $percentage = $limit > 0 ? ($spent / $limit) * 100 : 0;
            $health = 'good';
            if ($limit > 0) {
                if ($percentage >= 100)
                    $health = 'critical';
                elseif ($percentage >= 80)
                    $health = 'warning';
            }

            $status[] = [
                'category' => $cat,
                'limit' => $limit,
                'spent' => $spent,
                'remaining' => max(0, $limit - $spent),
                'percentage' => min(100, round($percentage, 1)),
                'health' => $health, // 'good', 'warning', 'critical'
                'is_set' => $limit > 0
            ];
        }

        return $status;
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
