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
        $this->createEmployeesTableIfNotExists();
        $this->createRequestsTableIfNotExists();
    }

    private function createEmployeesTableIfNotExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS workshift_employees (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            surname VARCHAR(100) NOT NULL DEFAULT '',
            role VARCHAR(50),
            department VARCHAR(50),
            email VARCHAR(100),
            employee_code VARCHAR(50) UNIQUE,
            fiscal_code VARCHAR(16),
            birth_date DATE,
            birth_place VARCHAR(100),
            gender VARCHAR(10),
            address TEXT,
            city VARCHAR(100),
            zip VARCHAR(10),
            phone VARCHAR(20),
            mobile VARCHAR(20),
            contract_type VARCHAR(50),
            contract_start DATE,
            contract_end DATE,
            auth_grade INT DEFAULT 1,
            notes TEXT,
            skills TEXT,
            avatar VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->pdo->exec($sql);
    }

    private function createRequestsTableIfNotExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS workshift_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            type VARCHAR(50) DEFAULT 'Ferie',
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            reason TEXT,
            status VARCHAR(20) DEFAULT 'Pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES workshift_employees(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->pdo->exec($sql);
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
            // Use range to cover entire day (DATETIME safe)
            $stmt = $this->pdo->prepare("DELETE FROM workshift_shifts WHERE date >= :start AND date <= :end");
            $stmt->execute(['start' => $dateOrStart . ' 00:00:00', 'end' => $dateOrStart . ' 23:59:59']);
        } elseif ($scope === 'week') {
            // Use >= and <= with end-of-day time to ensure DATETIME columns are fully covered
            $stmt = $this->pdo->prepare("DELETE FROM workshift_shifts WHERE date >= :start AND date <= :end");
            $stmt->execute(['start' => $dateOrStart, 'end' => $end . ' 23:59:59']);
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
        // Define all allowed fields based on schema and form
        $fields = [
            'name',
            'surname',
            'role',
            'department',
            'email',
            'employee_code',
            'fiscal_code',
            'birth_date',
            'birth_place',
            'gender',
            'address',
            'city',
            'zip',
            'phone',
            'mobile',
            'contract_type',
            'contract_start',
            'contract_end',
            'auth_grade',
            'notes',
            'skills',
            'avatar'
        ];

        // Prepare params, handling nulls
        $params = [];
        foreach ($fields as $field) {
            $params[$field] = $data[$field] ?? null;
        }

        if (isset($data['id']) && !empty($data['id'])) {
            $setClause = implode(', ', array_map(fn($f) => "$f = :$f", $fields));
            $sql = "UPDATE workshift_employees SET $setClause WHERE id = :id";
            $params['id'] = $data['id'];

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return (int) $data['id'];
        }

        $columns = implode(', ', $fields);
        $placeholders = implode(', ', array_map(fn($f) => ":$f", $fields));
        $sql = "INSERT INTO workshift_employees ($columns) VALUES ($placeholders)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $this->pdo->lastInsertId();
    }

    public function findAllEmployees(): array
    {
        $stmt = $this->pdo->query("SELECT *, CONCAT(name, ' ', surname) as full_name FROM workshift_employees ORDER BY surname ASC, name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findCandidates(string $query): array
    {
        $term = "%$query%";
        $isAll = empty(trim($query));

        // Search in existing Team
        // Force collation for literals and columns
        // Added employee_code to selection
        $sqlTeam = "SELECT id, name COLLATE utf8mb4_unicode_ci as name, surname COLLATE utf8mb4_unicode_ci as surname, role COLLATE utf8mb4_unicode_ci as role, email COLLATE utf8mb4_unicode_ci as email, employee_code, fiscal_code, 'Team' COLLATE utf8mb4_unicode_ci as source
                    FROM workshift_employees ";
        if (!$isAll) {
            $sqlTeam .= "WHERE name LIKE :q1 OR surname LIKE :q2 OR email LIKE :q3 OR employee_code LIKE :q4";
        }

        // Search in Security Center (Users)
        $sqlUsers = "SELECT id, username COLLATE utf8mb4_unicode_ci as name, NULL as surname, role COLLATE utf8mb4_unicode_ci as role, NULL as email, NULL as employee_code, NULL as fiscal_code, 'Security Center' COLLATE utf8mb4_unicode_ci as source
                     FROM users ";
        if (!$isAll) {
            $sqlUsers .= "WHERE username LIKE :q5";
        }

        $sql = "($sqlTeam) UNION ($sqlUsers) ORDER BY name ASC LIMIT 50";

        $stmt = $this->pdo->prepare($sql);

        if (!$isAll) {
            $stmt->execute(['q1' => $term, 'q2' => $term, 'q3' => $term, 'q4' => $term, 'q5' => $term]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    public function deleteEmployee(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM workshift_employees WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    // --- Request Methods ---

    public function findAllRequests(?int $employeeId = null, ?string $status = null): array
    {
        $sql = "SELECT r.*, e.name as employee_name, e.role as employee_role 
                FROM workshift_requests r 
                LEFT JOIN workshift_employees e ON r.employee_id = e.id 
                WHERE 1=1";
        $params = [];

        if ($employeeId) {
            $sql .= " AND r.employee_id = :employee_id";
            $params['employee_id'] = $employeeId;
        }

        if ($status) {
            $sql .= " AND r.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY r.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decorate with initials if missing from DB join (user_initial might not be in DB)
        foreach ($results as &$row) {
            if (empty($row['user_initial']) && !empty($row['employee_name'])) {
                $parts = explode(' ', $row['employee_name']);
                $initials = '';
                foreach ($parts as $part) {
                    $initials .= strtoupper(substr($part, 0, 1));
                }
                $row['employee_initials'] = substr($initials, 0, 2); // Matched with template {{employee_initials}}
            }
            // Helper bools for mustache
            $row['is_pending'] = ($row['status'] === 'Pending');
            $row['is_approved'] = ($row['status'] === 'Approved');
            $row['is_rejected'] = ($row['status'] === 'Rejected');
        }

        return $results ?: [];
    }

    public function saveRequest(array $data): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO workshift_requests (employee_id, type, start_date, end_date, reason, status) VALUES (:employee_id, :type, :start_date, :end_date, :reason, :status)");
        $stmt->execute([
            'employee_id' => $data['employee_id'],
            'type' => $data['type'] ?? 'Ferie',
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'] ?? '',
            'status' => $data['status'] ?? 'Pending'
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateRequestStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare("UPDATE workshift_requests SET status = :status WHERE id = :id");
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }

    public function deleteRequest(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM workshift_requests WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function deleteAllRequests(): int
    {
        $stmt = $this->pdo->prepare("DELETE FROM workshift_requests");
        $stmt->execute();
        return $stmt->rowCount();
    }

    // --- Analytics Methods ---

    // --- Analytics Methods ---

    public function getAnalyticsSummary(?string $start = null, ?string $end = null): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_shifts,
                    SUM(TIMESTAMPDIFF(HOUR, start_time, end_time)) as total_hours,
                    (SELECT COUNT(*) FROM workshift_requests WHERE status = 'Pending') as pending_requests
                FROM workshift_shifts
                WHERE 1=1";

        $params = [];
        if ($start && $end) {
            $sql .= " AND date BETWEEN :start AND :end";
            $params = ['start' => $start, 'end' => $end];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Calculate hypothetical cost (e.g. 20€/hour avg)
        $totalHours = (int) ($result['total_hours'] ?? 0);
        $cost = $totalHours * 20;

        return [
            'total_shifts' => (int) ($result['total_shifts'] ?? 0),
            'total_hours' => $totalHours,
            'total_cost' => $cost,
            'pending_requests' => (int) ($result['pending_requests'] ?? 0)
        ];
    }

    public function getMonthlyTrend(?string $start = null, ?string $end = null): array
    {
        $sql = "SELECT date, SUM(TIMESTAMPDIFF(HOUR, start_time, end_time)) as hours
                FROM workshift_shifts
                WHERE 1=1";

        $params = [];
        if ($start && $end) {
            $sql .= " AND date BETWEEN :start AND :end";
            $params = ['start' => $start, 'end' => $end];
        } else {
            $sql .= " AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        }

        $sql .= " GROUP BY date ORDER BY date ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getRoleDistribution(?string $start = null, ?string $end = null): array
    {
        $sql = "SELECT e.role, COUNT(*) as count
                FROM workshift_shifts s
                JOIN workshift_employees e ON s.employee_id = e.id
                WHERE 1=1";

        $params = [];
        if ($start && $end) {
            $sql .= " AND s.date BETWEEN :start AND :end";
            $params = ['start' => $start, 'end' => $end];
        }

        $sql .= " GROUP BY e.role";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
