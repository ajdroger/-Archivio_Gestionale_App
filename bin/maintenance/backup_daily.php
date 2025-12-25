<?php

require __DIR__ . '/../../vendor/autoload.php';

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;
use Psr\Log\LoggerInterface;

// 1. Security Check (CLI Only)
if (php_sapi_name() !== 'cli') {
    die('Access Denied: CLI only.');
}

// 2. Load Env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// 3. Setup
$rootDir = realpath(__DIR__ . '/../../');
$backupDir = $rootDir . '/storage/backups';
$date = date('Y-m-d_H-i-s');
$filename = "backup_full_{$date}.zip";
$zipPath = $backupDir . '/' . $filename;

// Ensure backup dir exists
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

echo "Starting Backup...\n";
echo "Target: {$zipPath}\n";

$zipPath = $backupDir . '/' . $filename;

// Ensure backup dir exists
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

echo "Starting Backup...\n";
echo "Target: {$zipPath}\n";

$dbFile = $rootDir . '/database.sqlite';
$uploadDir = $rootDir . '/storage/uploads';

if (class_exists('ZipArchive')) {
    echo "Using PHP ZipArchive...\n";
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        die("Error: Cannot create zip file.\n");
    }

    // 4. Add Database
    if (file_exists($dbFile)) {
        echo "Adding database.sqlite...\n";
        $zip->addFile($dbFile, 'database.sqlite');
    }

    // 5. Add Uploads
    if (is_dir($uploadDir)) {
        echo "Adding uploads...\n";
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($uploadDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = 'storage/uploads/' . substr($filePath, strlen($uploadDir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
    $zip->close();

} elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    echo "ZipArchive not found. Using PowerShell Compress-Archive...\n";

    $paths = [];
    if (file_exists($dbFile))
        $paths[] = $dbFile;
    if (is_dir($uploadDir))
        $paths[] = $uploadDir;

    if (empty($paths)) {
        die("Nothing to backup.\n");
    }

    // Escape paths for PowerShell
    $pathArgs = implode(', ', array_map(fn($p) => "'$p'", $paths));

    // Command
    $cmd = "powershell -Command \"Compress-Archive -Path @($pathArgs) -DestinationPath '$zipPath' -Force\"";

    echo "Executing: $cmd\n";
    exec($cmd, $output, $returnCode);

    if ($returnCode !== 0) {
        echo "PowerShell Error Output:\n" . implode("\n", $output) . "\n";
        die("Error: PowerShell backup failed.\n");
    }

} else {
    die("Error: ZipArchive extension missing and not on Windows.\n");
}

echo "Backup created successfully.\n";

// 6. Retention Policy (Delete > 7 days)
echo "Running retention policy (7 days)...\n";
$retentionDays = 7;
$files = glob($backupDir . '/*.zip');
$now = time();

foreach ($files as $file) {
    if (is_file($file)) {
        if ($now - filemtime($file) >= 60 * 60 * 24 * $retentionDays) {
            echo "Deleting old backup: " . basename($file) . "\n";
            unlink($file);
        }
    }
}

echo "Backup process completed.\n";
