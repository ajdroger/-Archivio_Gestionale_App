<?php

require __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

// 1. Load Environment Variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

$backupDir = __DIR__ . '/../../storage/backups';

echo "=== MCAG Database Restore ===\n";

// 2. Find latest backup file
$files = glob($backupDir . '/database_backup_*.sql');
if (empty($files)) {
    echo "[ERROR] No backup files found in $backupDir\n";
    exit(1);
}

// Sort by modification time (newest first)
usort($files, function ($a, $b) {
    return filemtime($b) - filemtime($a);
});

$latestBackup = $files[0];
echo "Latest Backup Found: " . basename($latestBackup) . " (" . date('Y-m-d H:i:s', filemtime($latestBackup)) . ")\n";

// 3. Confirm Restore
// In automated mode, we proceed. Usage: php restore.php --confirm
// For now, promptless if executed explicitly? 
// User asked to "restore last backup", implying immediate action.

echo "Restoring database from $latestBackup...\n";

// 4. Determine Database Config
$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$user = $_ENV['DB_USERNAME'] ?? 'root';
$pass = $_ENV['DB_PASSWORD'] ?? '';
$db = $_ENV['DB_DATABASE'] ?? 'fratellanza_db';

// 5. Detect MySQL Path (Windows Support)
$mysql = 'mysql';
if (stripos(PHP_OS, 'WIN') === 0) {
    $candidates = [
        'C:/Program Files/Ampps/mysql/bin/mysql.exe',
        'C:/xampp/mysql/bin/mysql.exe'
    ];
    foreach ($candidates as $candidate) {
        if (file_exists($candidate)) {
            $mysql = '"' . $candidate . '"';
            break;
        }
    }
}

// 6. Build Command
$passPart = empty($pass) ? '' : "--password=\"$pass\"";
$cmd = "$mysql --host=$host --user=$user $passPart $db < \"$latestBackup\" 2>&1";

// 7. Execute
$output = [];
$resultCode = 0;
exec($cmd, $output, $resultCode);

if ($resultCode === 0) {
    echo "[SUCCESS] Database restored successfully.\n";
    exit(0);
} else {
    echo "[ERROR] Restore failed (Code $resultCode).\n";
    echo implode("\n", $output) . "\n";
    exit(1);
}
