<?php
require __DIR__ . '/../../vendor/autoload.php';

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;

try {
    // 1. Load Dotenv explicitly to be sure
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();

    // 2. Connect
    $db = DatabaseConnection::getConnection();

    // 3. Query Users
    $stmt = $db->query("SELECT id, username, role, totp_secret FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "--- USERS TABLE DUMP ---\n";
    foreach ($users as $u) {
        echo "ID: " . $u['id'] .
            " | User: " . str_pad($u['username'], 15) .
            " | Role: " . str_pad($u['role'], 12) .
            " | 2FA: " . ($u['totp_secret'] ? 'YES' : 'NO') . "\n";
    }
    echo "------------------------\n";

    // 4. Check Session (if running from browser, this script won't see it, but good to know DB state first)

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
