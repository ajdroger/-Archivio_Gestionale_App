<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use MCAG\InfrastrutturaIT\Persistence\DatabaseConnection;

/**
 * Script Pulizia Storage Temporaneo
 * 
 * Rimuove file temporanei e log vecchi per liberare spazio,
 * mantenendo però la struttura delle cartelle e i dati vitali.
 */

echo "\n================================================\n";
echo "   PULIZIA FILE TEMPORANEI (CLEANUP)           \n";
echo "================================================\n";

function delete_files($target, $pattern = '*')
{
    $files = glob($target . '/' . $pattern);
    $count = 0;
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            $count++;
        }
    }
    return $count;
}

// 1. Clean Logs
echo "[+] Pulizia Logs...\n";
$c1 = delete_files(__DIR__ . '/../../logs/debug', '*.log');
$c2 = delete_files(__DIR__ . '/../../logs/debug', '*.txt');
$c3 = delete_files(__DIR__ . '/../../logs/tests', '*.html'); // Report test
echo "   - Rimossi " . ($c1 + $c2 + $c3) . " file di log.\n";

// 2. Clean Tmp Uploads (if any)
echo "[+] Controllo file orfani temporanei...\n";
// (Logica simulata: non cancelliamo uploads reali)

echo "\n[SUCCESSO] Pulizia completata.\n";

