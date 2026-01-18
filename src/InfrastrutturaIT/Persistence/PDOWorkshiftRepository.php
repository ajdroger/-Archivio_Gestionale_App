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

    public function findShiftsByRange(string $start, string $end): array
    {
        $stmt = $this->pdo->prepare("SELECT s.*, e.name as employee_name, e.role as employee_role FROM workshift_shifts s LEFT JOIN workshift_employees e ON s.employee_id = e.id WHERE s.date BETWEEN :start AND :end ORDER BY s.date, s.start_time");
        $stmt->execute(['start' => $start, 'end' => $end]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM workshift_shifts WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function deleteAllShifts(string $scope, string $dateOrStart, ?string $end = null): int
    {
        if ($scope === 'day') {
            $stmt = $this->pdo->prepare("DELETE FROM workshift_shifts WHERE date = :date");
            $stmt->execute(['date' => $dateOrStart]);
        } elseif ($scope === 'week') {
            $stmt = $this->pdo->prepare("DELETE FROM workshift_shifts WHERE date BETWEEN :start AND :end");
            $stmt->execute(['start' => $dateOrStart, 'end' => $end]);
        } else {
            return 0;
        }
        return $stmt->rowCount();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, ['employee_id', 'start_time', 'end_time', 'type', 'day', 'date'])) {
                $fields[] = "$key = :$key";
                $params[$key] = $value;
            }
        }

        if (empty($fields))
            return false;

        $sql = "UPDATE workshift_shifts SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function save(array $data): int
    {
        if (isset($data['id']) && !empty($data['id'])) {
            $this->update((int) $data['id'], $data);
            return (int) $data['id'];
        }

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

    public function saveEmployee(array $data): int
    {
        if (isset($data['id']) && !empty($data['id'])) {
            $stmt = $this->pdo->prepare("UPDATE workshift_employees SET name=:name, role=:role, department=:department, email=:email WHERE id=:id");
            $stmt->execute([
                'id' => $data['id'],
                'name' => $data['name'],
                'role' => $data['role'],
                'department' => $data['department'],
                'email' => $data['email']
            ]);
            return (int) $data['id'];
        }

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
        $stmt = $this->pdo->query("SELECT * FROM workshift_employees ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findCandidates(string $query): array
    {
        $term = "%$query%";
        $isAll = empty(trim($query));

        // Search in existing Team
        // Force collation for literals and columns
        $sqlTeam = "SELECT id, name COLLATE utf8mb4_unicode_ci as name, role COLLATE utf8mb4_unicode_ci as role, email COLLATE utf8mb4_unicode_ci as email, 'Team' COLLATE utf8mb4_unicode_ci as source
                    FROM workshift_employees ";
        if (!$isAll) {
            $sqlTeam .= "WHERE name LIKE :q1 OR email LIKE :q2";
        }

        // Search in Security Center (Users)
        $sqlUsers = "SELECT id, username COLLATE utf8mb4_unicode_ci as name, role COLLATE utf8mb4_unicode_ci as role, NULL as email, 'Security Center' COLLATE utf8mb4_unicode_ci as source
                     FROM users ";
        if (!$isAll) {
            $sqlUsers .= "WHERE username LIKE :q3";
        }

        $sql = "($sqlTeam) UNION ($sqlUsers) ORDER BY name ASC LIMIT 50";

        $stmt = $this->pdo->prepare($sql);

        if (!$isAll) {
            $stmt->execute(['q1' => $term, 'q2' => $term, 'q3' => $term]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
