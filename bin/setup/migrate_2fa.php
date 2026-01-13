<?php
require __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

echo "Migration Started: Per-User 2FA...\n";

try {
    $db = \MCAG\InfrastrutturaIT\Persistence\DatabaseConnection::getConnection();

    // 1. Add Column
    $cols = $db->query("PRAGMA table_info(users)")->fetchAll(\PDO::FETCH_ASSOC);
    $hasCol = false;
    foreach ($cols as $col) {
        if ($col['name'] === 'totp_secret')
            $hasCol = true;
    }

    if (!$hasCol) {
        $db->exec("ALTER TABLE users ADD COLUMN totp_secret TEXT NULL");
        echo "Column 'totp_secret' added.\n";
    }

    // 2. Backfill with Legacy Secret
    $legacySecret = $_ENV['TOTP_SECRET'] ?? '';
    if (!empty($legacySecret)) {
        $stmt = $db->prepare("UPDATE users SET totp_secret = :secret WHERE totp_secret IS NULL");
        $stmt->execute([':secret' => $legacySecret]);
        echo "Backfilled " . $stmt->rowCount() . " users with legacy secret.\n";
    }

    echo "Migration Complete.\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

