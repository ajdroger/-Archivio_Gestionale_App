<?php
/**
 * Script Ripristino Permessi (Windows/Simulato)
 * 
 * Assicura che le cartelle di storage e log siano scrivibili.
 * (Su Windows non ha lo stesso effetto di chmod, ma verifica l'accessibilità).
 */

echo "\n================================================\n";
echo "   RIPRISTINO PERMESSI FILESYSTEM              \n";
echo "================================================\n";

$paths = [
    __DIR__ . '/../../storage',
    __DIR__ . '/../../storage/uploads',
    __DIR__ . '/../../storage/backups',
    __DIR__ . '/../../logs',
    __DIR__ . '/../../logs/debug',
    __DIR__ . '/../../logs/tests'
];

$errors = 0;

foreach ($paths as $path) {
    echo "Checking: " . realpath($path) . "... ";

    if (!file_exists($path)) {
        if (mkdir($path, 0777, true)) {
            echo "[CREATED]\n";
        } else {
            echo "[ERROR CREATING]\n";
            $errors++;
        }
    } else {
        if (is_writable($path)) {
            echo "[OK - WRITABLE]\n";
        } else {
            // Tentativo fix (chmod)
            if (@chmod($path, 0777)) {
                echo "[FIXED]\n";
            } else {
                echo "[ERROR - NOT WRITABLE]\n";
                $errors++;
            }
        }
    }
}

if ($errors === 0) {
    echo "\n[SUCCESSO] Tutti i permessi sono corretti.\n";
} else {
    echo "\n[ATTENZIONE] Ci sono stati $errors errori di permesso.\n";
}

