<?php
require __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;

echo "--- Soft Delete Integrity Check ---\n";

try {
    $pdo = DatabaseConnection::getConnection();

    $tables = ['soci', 'documenti', 'users'];

    foreach ($tables as $t) {
        // Check column exists
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM `$t` LIKE 'deleted_at'");
            if ($stmt->fetch()) {
                echo "[OK] Table '$t' has 'deleted_at' column.\n";

                // Count Stats
                $total = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
                $active = $pdo->query("SELECT COUNT(*) FROM `$t` WHERE deleted_at IS NULL")->fetchColumn();
                $deleted = $pdo->query("SELECT COUNT(*) FROM `$t` WHERE deleted_at IS NOT NULL")->fetchColumn();

                echo "     Total: $total | Active: $active | Deleted: $deleted\n";
            } else {
                echo "[WARN] Table '$t' MISSING 'deleted_at' column.\n";
            }
        } catch (\PDOException $e) {
            echo "[ERR] Checking table '$t': " . $e->getMessage() . "\n";
        }
    }

} catch (\Throwable $e) {
    echo "[FATAL] " . $e->getMessage() . "\n";
}
