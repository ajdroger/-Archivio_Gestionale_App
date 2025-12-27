<?php

require __DIR__ . '/../../vendor/autoload.php';

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;

// Load Env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

try {
    echo "Restoring Users...\n";
    $pdo = DatabaseConnection::getConnection();

    // Default Users
    $users = [
        [
            'username' => 'admin',
            'password' => 'admin123',
            'role' => 'admin'
        ],
        [
            'username' => 'segreteria',
            'password' => 'secret',
            'role' => 'editor'
        ],
        [
            'username' => 'comando',
            'password' => 'comando',
            'role' => 'viewer'
        ]
    ];

    foreach ($users as $u) {
        // Check if exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :u");
        $stmt->execute(['u' => $u['username']]);
        if ($stmt->fetch()) {
            echo "User {$u['username']} already exists. Updating password...\n";
            $hash = password_hash($u['password'], PASSWORD_BCRYPT);
            $upd = $pdo->prepare("UPDATE users SET password_hash = :p, role = :r WHERE username = :u");
            $upd->execute(['p' => $hash, 'r' => $u['role'], 'u' => $u['username']]);
        } else {
            echo "Creating user {$u['username']}...\n";
            $hash = password_hash($u['password'], PASSWORD_BCRYPT);
            $ins = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (:u, :p, :r)");
            $ins->execute(['u' => $u['username'], 'p' => $hash, 'r' => $u['role']]);
        }
    }

    echo "Done.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
