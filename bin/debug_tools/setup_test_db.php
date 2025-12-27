<?php
require __DIR__ . '/../../vendor/autoload.php';

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;

echo ">>> SETTING UP TEST DATABASE <<<\n";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$user = $_ENV['DB_USERNAME'] ?? 'root';
$pass = $_ENV['DB_PASSWORD'] ?? '';
$testDb = 'fratellanza_test';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "[1] Recreating Database '$testDb'...\n";
    $pdo->exec("DROP DATABASE IF EXISTS `$testDb`");
    $pdo->exec("CREATE DATABASE `$testDb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✔ Database Created.\n";

} catch (PDOException $e) {
    die("✘ DB Setup Failed: " . $e->getMessage() . "\n");
}

echo "[2] Running Migrations (Phinx)...\n";
// Using passthru to run phinx command
$cmd = "vendor\\bin\\phinx migrate -e testing -c config/phinx.php";
if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
    $cmd = "./vendor/bin/phinx migrate -e testing -c config/phinx.php";
}
echo "Executing: $cmd\n";

passthru($cmd, $returnVar);

if ($returnVar === 0) {
    echo "\n✔ Test Database Ready!\n";

    // Update Admin User Password for Tests (Migration seeds 'password', tests likely need 'admin123')
    $pdo->exec("USE `$testDb`");
    $passHash = password_hash('admin123', PASSWORD_BCRYPT);
    // Use UPDATE because migration already inserted 'admin'
    $pdo->exec("UPDATE users SET password_hash = '$passHash', role = 'admin' WHERE username = 'admin'");
    echo "✔ Admin User Updated (password_hash set).\n";

} else {
    echo "\n✘ Migration Failed!\n";
    exit(1);
}
