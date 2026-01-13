<?php

/**
 * System Cleanup Utility
 * Usage: php bin/cleanup_system.php
 */

require __DIR__ . '/../../vendor/autoload.php';

$dirsToClean = [
    __DIR__ . '/../storage/uploads/temp',
    sys_get_temp_dir() . '/fm_ratelimit'
];

echo "Starts Cleanup...\n";

foreach ($dirsToClean as $dir) {
    if (!is_dir($dir)) {
        echo "Skipping non-existent dir: $dir\n";
        continue;
    }

    echo "Cleaning: $dir\n";
    $files = glob("$dir/*");
    $count = 0;
    foreach ($files as $file) {
        if (is_file($file)) {
            // Delete files older than 24 hours
            if (time() - filemtime($file) > 86400) {
                unlink($file);
                $count++;
            }
        }
    }
    echo "Removed $count old files.\n";
}

// Rotate Logs
$logDir = __DIR__ . '/../../logs';
if (is_dir($logDir)) {
    echo "Checking Logs recursively in $logDir...\n";
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($logDir));
    foreach ($iterator as $file) {
        if ($file->isFile() && in_array($file->getExtension(), ['log', 'txt'])) {
            if ($file->getSize() > 10 * 1024 * 1024) { // 10MB
                $filePath = $file->getRealPath();
                echo "Rotating $filePath...\n";
                rename($filePath, $filePath . '.' . date('Y-m-d-His') . '.bak');
            }
        }
    }
}

echo "Cleanup Completed.\n";

