<?php
require __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

// Load Environment
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

// Configuration
$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$user = $_ENV['DB_USERNAME'] ?? 'root';
$pass = $_ENV['DB_PASSWORD'] ?? '';
$dbName = $_ENV['DB_DATABASE'] ?? 'fratellanza_db';
$backupDir = __DIR__ . '/../../backups/safety_snapshots';

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

$timestamp = date('Ymd_His');
$backupFile = "$backupDir/pre_test_snapshot_$timestamp.sql";

echo "\n🛡️  SAFE TEST RUNNER v1.0 🛡️\n";
echo "====================================\n";
echo "Target Database: $dbName\n";

// 1. Connect to DB
try {
    $dsn = "mysql:host=$host;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("❌ Connection Failed: " . $e->getMessage() . "\n");
}

// 2. Initial Integrity Check
echo "[1/5] Checking Initial Data Integrity...\n";
$tables = ['soci', 'users', 'documenti'];
$initialCounts = [];

foreach ($tables as $table) {
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        $initialCounts[$table] = $count;
        echo "  - $table: $count rows\n";
    } catch (Exception $e) {
        // Table might not exist yet, handled graciously
        $initialCounts[$table] = 0;
    }
}

// 3. Perform Backup
echo "\n[2/5] Creating Safety Backup...\n";
$mysqldump = "mysqldump";
if (stripos(PHP_OS, 'WIN') === 0) {
    // Attempt to locate mysqldump on standard windows paths if simpler command fails
    $candidates = [
        'C:/Program Files/Ampps/mysql/bin/mysqldump.exe',
        'C:/xampp/mysql/bin/mysqldump.exe'
    ];
    foreach ($candidates as $candidate) {
        if (file_exists($candidate)) {
            $mysqldump = '"' . $candidate . '"';
            break;
        }
    }
}

$passArg = empty($pass) ? '' : "--password=\"$pass\"";
// Routines + Triggers for full fidelity
$cmd = "$mysqldump --host=$host --user=$user $passArg --routines --triggers --add-drop-table \"$dbName\" > \"$backupFile\" 2>&1";

exec($cmd, $output, $res);
if ($res !== 0) {
    echo "❌ Backup Failed! Aborting tests to protect data.\n";
    echo implode("\n", $output);
    exit(1);
}
echo "✔ Backup saved to: " . basename($backupFile) . "\n";

// 4. Run Tests
echo "\n[3/5] Running Test Suite (Pest)...\n";
echo "------------------------------------\n";
// Pass through the command output live
$pestCmd = (stripos(PHP_OS, 'WIN') === 0) ? '..\\..\\vendor\\bin\\pest' : '../../vendor/bin/pest';
passthru("$pestCmd --colors=always", $testExitCode);
echo "------------------------------------\n";

// 5. Post-Test Integrity Check
echo "\n[4/5] Verifying Post-Test Data Integrity...\n";
$damageDetected = false;
foreach ($tables as $table) {
    $currentCount = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    if ($currentCount != $initialCounts[$table]) {
        echo "⚠️  DATA LOSS DETECTED in table '$table'! (Started: {$initialCounts[$table]}, Now: $currentCount)\n";
        $damageDetected = true;
    } else {
        echo "  - $table: Safe ($currentCount)\n";
    }
}

// 6. Restore Logic
if ($damageDetected) {
    echo "\n🚨 CRITICAL: PRODUCTION DATA WAS MODIFIED DURING TESTS! 🚨\n";
    echo "[5/5] Initiating EMERGENCY RESTORE...\n";

    $mysql = "mysql";
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

    $restoreCmd = "$mysql --host=$host --user=$user $passArg \"$dbName\" < \"$backupFile\" 2>&1";
    exec($restoreCmd, $restoreOut, $restoreRes);

    if ($restoreRes === 0) {
        echo "\n✅ RESTORE COMPLETE. Your data has been recovered.\n";
    } else {
        echo "\n❌ RESTORE FAILED. Please verify file: $backupFile manually.\n";
        echo implode("\n", $restoreOut);
    }
} else {
    echo "\n✅ SYSTEM INTEGRITY CONFIRMED. No data was lost.\n";
    // Optional: Cleanup backup if safe? Usually better to keep just in case.
    // unlink($backupFile); 
}

exit($testExitCode);
