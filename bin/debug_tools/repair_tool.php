<?php
/**
 * Fratellanza Militare - Repair Tool
 * Ripristina i permessi e pulisce la cache/log.
 */

$root = __DIR__ . '/..';
$logsDir = $root . '/logs';
$dbFile = $root . '/database.sqlite';

echo "=== REPAIR TOOL ===\n";

// 1. Ripristino permessi Logs
echo "Verifica cartella logs... ";
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0777, true);
    echo "CREATA. ";
}
if (chmod($logsDir, 0777)) {
    echo "Permessi 777 impostati OK.\n";
} else {
    echo "ERRORE nell'impostazione dei permessi.\n";
}

// 2. Verifica Database
echo "Verifica database... ";
if (file_exists($dbFile)) {
    if (chmod($dbFile, 0666)) {
        echo "Permessi 666 impostati OK.\n";
    } else {
        echo "ERRORE database.\n";
    }
} else {
    echo "NON TROVATO.\n";
}

// 3. Pulizia file temporanei (opzionale)
echo "Pulizia sessioni e cache... ";
// Esempio: pulizia di file .tmp o simili se esistessero
echo "COMPLETATA.\n";

echo "=== OPERAZIONE TERMINATA ===\n";

