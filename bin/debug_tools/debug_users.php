<?php
require __DIR__ . '/../../vendor/autoload.php';

if (php_sapi_name() !== 'cli') {
    die('Access Denied: CLI only.');
}

try {
    $db = \MCAG\InfrastrutturaIT\Persistence\DatabaseConnection::getConnection();

    // Check tables
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetchAll(\PDO::FETCH_ASSOC);
    if (empty($tables)) {
        echo "Table 'users' DOES NOT EXIST.\n";

        // Create it if missing (Bootstrapping?)
        $db->exec("CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            role TEXT DEFAULT 'user',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        echo "Table 'users' created.\n";

        // Seed Admin
        $pass = password_hash('password', PASSWORD_BCRYPT);
        $db->exec("INSERT INTO users (username, password_hash, role) VALUES ('admin', '$pass', 'admin')");
        echo "Admin seeded (admin/password).\n";
    }

    // Check columns
    $cols = $db->query("PRAGMA table_info(users)")->fetchAll(\PDO::FETCH_ASSOC);
    print_r($cols);

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}

