<?php
/**
 * Script di manutenzione per svuotare la cache di sistema.
 * Percorso: bin/maintenance/clear_cache.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use MCAG\Utils\Path;

$cacheDir = __DIR__ . '/../../cache';
$output = [];

if (!is_dir($cacheDir)) {
    echo "Nessuna cache da pulire (Directory non presente).\n";
    exit(0); // Exit successfully
}

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($cacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
);

$deletedFiles = 0;
$deletedDirs = 0;

foreach ($files as $fileinfo) {
    if ($fileinfo->getFilename() === '.gitignore' || $fileinfo->getFilename() === '.gitkeep') {
        continue;
    }

    $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
    if ($todo($fileinfo->getRealPath())) {
        if ($fileinfo->isDir())
            $deletedDirs++;
        else
            $deletedFiles++;
    }
}

echo "Cache pulita con successo.\n";
echo "File rimossi: $deletedFiles\n";
echo "Cartelle rimosse: $deletedDirs\n";

