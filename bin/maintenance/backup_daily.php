<?php

require __DIR__ . '/../../vendor/autoload.php';

use FratellanzaMilitare\Service\BackupService;
use Psr\Log\AbstractLogger;

// Simple logger so we see output
class ConsoleLogger extends AbstractLogger
{
    public function log($level, $message, array $context = []): void
    {
        echo strtoupper($level) . ": $message\n";
    }
}

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

// 4. Generate MySQL Dump via Service
echo "Generating MySQL Dump...\n";
$service = new BackupService('', $backupDir, new ConsoleLogger(), 7); // 7 days retention for SQLs
if (!$service->executeBackup()) {
    echo "Warning: MySQL Dump failed. Proceeding with files only.\n";
}

// Find latest .sql to include in ZIP
$sqlFiles = glob($backupDir . '/*.sql');
usort($sqlFiles, fn($a, $b) => filemtime($b) - filemtime($a));
$latestSql = $sqlFiles[0] ?? null;

// 5. Create ZIP
$uploadDir = $rootDir . '/storage/uploads';

if (class_exists('ZipArchive')) {
    echo "Using PHP ZipArchive...\n";
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        die("Error: Cannot create zip file.\n");
    }

    // Add Database Dump
    if ($latestSql) {
        echo "Adding database dump: " . basename($latestSql) . "\n";
        $zip->addFile($latestSql, 'database_dump.sql');
    }

    // Add Uploads
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

    $pathsToZip = [];
    if ($latestSql) {
        $pathsToZip[] = $latestSql;
    }
    if (is_dir($uploadDir)) {
        $pathsToZip[] = $uploadDir;
    }

    if (empty($pathsToZip)) {
        die("Nothing to backup.\n");
    }

    // Escape paths
    $pathArgs = implode(', ', array_map(fn($p) => "'$p'", $pathsToZip));
    // Destination needs to be absolute
    $dest = realpath($backupDir) . DIRECTORY_SEPARATOR . $filename;

    $cmd = "powershell -Command \"Compress-Archive -Path @($pathArgs) -DestinationPath '$dest' -Force\"";
    echo "Executing: $cmd\n";

    exec($cmd, $output, $returnCode);

    if ($returnCode !== 0) {
        echo "PowerShell Error Output:\n" . implode("\n", $output) . "\n";
        die("Error: PowerShell backup failed. Code: $returnCode\n");
    }
}

echo "Backup ZIP created successfully.\n";

// 6. Retention Policy for ZIPs (Delete > 7 days)
echo "Running retention policy for ZIPs (7 days)...\n";
$retentionDays = 7;
$files = glob($backupDir . '/*.zip');
$now = time();

foreach ($files as $file) {
    if (is_file($file)) {
        if ($now - filemtime($file) >= 60 * 60 * 24 * $retentionDays) {
            echo "Deleting old backup ZIP: " . basename($file) . "\n";
            unlink($file);
        }
    }
}

echo "Backup process completed.\n";
