<?php

declare(strict_types=1);

namespace MCAG\InfrastrutturaIT\Persistence;

use PDO;

class PDOWorkshiftRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findAll(): array
    {
        // Placeholder implementation
        $stmt = $this->pdo->query("SELECT * FROM workshift_shifts ORDER BY date DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getAllShifts(): array
    {
        return $this->findAll();
    }

    public function save(array $data): int
    {
        // Simple Insert (Update not used in test yet)
        $stmt = $this->pdo->prepare("INSERT INTO workshift_shifts (employee_id, start_time, end_time, type, day, date) VALUES (:employee_id, :start_time, :end_time, :type, :day, :date)");
        $stmt->execute([
            'employee_id' => $data['employee_id'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'type' => $data['type'],
            'day' => $data['day'],
            'date' => $data['date']
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function delete(int $id): bool
    {
        // Placeholder implementation
        return true;
    }

    public function saveEmployee(array $data): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO workshift_employees (name, role, department, email) VALUES (:name, :role, :department, :email)");
        $stmt->execute([
            'name' => $data['name'],
            'role' => $data['role'],
            'department' => $data['department'],
            'email' => $data['email']
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function findAllEmployees(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM workshift_employees");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function deleteEmployee(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM workshift_employees WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
