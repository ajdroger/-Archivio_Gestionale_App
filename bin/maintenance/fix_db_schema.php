<?php

$host = '127.0.0.1';
$port = '3306';
$db = 'fratellanza_test';
$user = 'root';
$pass = ''; // Try empty first
// If empty fails, script will retry with 'mysql'

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Connected to DB: $db\n";
} catch (PDOException $e) {
    try {
        $pass = 'mysql';
        $pdo = new PDO($dsn, $user, $pass, $options);
        echo "Connected to DB: $db (using password 'mysql')\n";
    } catch (PDOException $e2) {
        die("Connection failed: " . $e->getMessage() . " AND " . $e2->getMessage() . "\n");
    }
}

// Fix Workshift Employees - DROP and RECREATE to update full schema
try {
    echo "Dropping workshift_requests (FK dependency)...\n";
    $pdo->exec("DROP TABLE IF EXISTS workshift_requests");

    echo "Dropping workshift_employees...\n";
    $pdo->exec("DROP TABLE IF EXISTS workshift_employees");

    echo "Creating workshift_employees with full schema...\n";
    // SQL copied from PDOWorkshiftRepository
    $sql = "CREATE TABLE IF NOT EXISTS workshift_employees (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
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
    $pdo->exec($sql);
    echo "SUCCESS: Recreated workshift_employees.\n";
} catch (PDOException $e) {
    echo "ERROR workshift_employees: " . $e->getMessage() . "\n";
}

// Fix Taskflow Tasks - DROP and RECREATE
try {
    echo "Dropping taskflow_tasks...\n";
    $pdo->exec("DROP TABLE IF EXISTS taskflow_tasks");

    echo "Creating taskflow_tasks...\n";
    $sql = "CREATE TABLE IF NOT EXISTS taskflow_tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            text TEXT NOT NULL,
            completed TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    echo "SUCCESS: Recreated taskflow_tasks.\n";
} catch (PDOException $e) {
    echo "ERROR taskflow_tasks: " . $e->getMessage() . "\n";
}
