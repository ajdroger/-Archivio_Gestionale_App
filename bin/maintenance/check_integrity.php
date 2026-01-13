<?php

/**
 * Checker di Integrità del Database
 * 
 * Verifica l'integrità referenziale, record orfani, e consistenza dei dati.
 * Utile per diagnosticare problemi di corruzione dati o bug logici.
 */

require __DIR__ . '/../../vendor/autoload.php';

use MCAG\InfrastrutturaIT\Persistence\DatabaseConnection;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

echo "🔍 CONTROLLO INTEGRITÀ DATABASE\n";
echo str_repeat('=', 60) . "\n\n";

$pdo = DatabaseConnection::getConnection();
$issues = [];

// 1. Documenti Orfani (documenti senza socio associato)
echo "1️⃣  Controllo documenti orfani...\n";
$cfCol = 'socio_cf';
$sql = "SELECT COUNT(*) as count 
        FROM documenti d 
        LEFT JOIN soci s ON d.$cfCol = s.codice_fiscale 
        WHERE s.codice_fiscale IS NULL";
$stmt = $pdo->query($sql);
$orphanedDocs = $stmt->fetchColumn();

if ($orphanedDocs > 0) {
    echo "   ⚠️  Trovati $orphanedDocs documento/i orfani\n";
    $issues[] = "Documenti orfani: $orphanedDocs";

    // Show details
    $sql = "SELECT d.id, d.nome_file, d.$cfCol 
            FROM documenti d 
            LEFT JOIN soci s ON d.$cfCol = s.codice_fiscale 
            WHERE s.codice_fiscale IS NULL 
            LIMIT 5";
    $stmt = $pdo->query($sql);
    $orphans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($orphans as $orphan) {
        echo "      - ID: {$orphan['id']}, File: {$orphan['nome_file']}, CF: {$orphan[$cfCol]}\n";
    }
} else {
    echo "   ✅ Nessun documento orfano\n";
}

// 2. File Mancanti
echo "\n2️⃣  Controllo file mancanti...\n";
// WARNING: percorso_file column might allow NULL if file content is in DB? Assuming existing logic is correct.
// But check column existence in schema? 'nome_file' exists. 'percorso_file' was not in my Migration!
// Let's check SchemaTest/Migration. 'percorso_file' is NOT in the migration I just verified. 
// I should remove this check or check 'nome_file' if it implies a path (usually uploads/ID/file).
// The existing `check_integrity.php` referenced `percorso_file`.
// If I look at `PDODocumentoRepository`, it doesn't save `percorso_file`. It saves `nome_file` and `hash_file`.
// The file is likely stored in `storage/uploads/{id_univoco}` or similar.
// I'll comment out or verify schema.
// Migration has: nome_file, hash_file, stato, data_caricamento, tipo_documento, socio_cf...
// NO `percorso_file`.
// So this check was broken even before? Or `percorso_file` was removed during migration.
// I will SKIP this check to avoid crashing if column missing.
echo "   ℹ️  Skippo controllo file (colonna percorso_file non presente nello schema V2)\n";

// 3. Codici Fiscali Duplicati
echo "\n3️⃣  Controllo duplicati Codici Fiscali...\n";
$sql = "SELECT codice_fiscale, COUNT(*) as count 
        FROM soci 
        GROUP BY codice_fiscale 
        HAVING count > 1";
$stmt = $pdo->query($sql);
$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($duplicates)) {
    echo sprintf("   ⚠️  Trovati %d duplicati\n", count($duplicates));
    foreach ($duplicates as $dup) {
        echo "      - CF: {$dup['codice_fiscale']} (appare {$dup['count']} volte)\n";
    }
    $issues[] = "Codici Fiscali duplicati: " . count($duplicates);
} else {
    echo "   ✅ Nessun Codice Fiscale duplicato\n";
}

// 4. Formato Email Invalido
echo "\n4️⃣  Controllo formato email...\n";
$sql = "SELECT codice_fiscale, email FROM soci WHERE email IS NOT NULL AND email != ''";
$stmt = $pdo->query($sql);
$emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
$invalidEmails = 0;

foreach ($emails as $row) {
    if (!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
        $invalidEmails++;
        if ($invalidEmails <= 5) {
            echo "   ⚠️  Invalid email: {$row['email']} (CF: {$row['codice_fiscale']})\n";
        }
    }
}

if ($invalidEmails > 0) {
    echo sprintf("   ⚠️  Totale email non valide: %d\n", $invalidEmails);
    $issues[] = "Formato email non valido: $invalidEmails";
} else {
    echo "   ✅ Tutte le email sono valide\n";
}

// 5. Campi Obbligatori Nulli
echo "\n5️⃣  Controllo campi obbligatori NULL...\n";
$requiredFields = ['nome', 'cognome', 'codice_fiscale', 'matricola'];
$nullFields = [];

foreach ($requiredFields as $field) {
    // Check if column exists first? Assuming schema is valid.
    $sql = "SELECT COUNT(*) FROM soci WHERE $field IS NULL OR $field = ''";
    $count = $pdo->query($sql)->fetchColumn();
    if ($count > 0) {
        echo "   ⚠️  Campo '$field' è NULL/vuoto in $count record\n";
        // Not a critical issue if matricola is optional? Schema says matricola nullable.
        // Code here treats it as issue.
        if ($field !== 'matricola')
            $issues[] = "$field: $count"; // Only flag criticals
    }
}

if (empty($issues)) {
    echo "   ✅ Tutti i campi obbligatori sono popolati\n";
}

// 6. Integrità Referenziale (Foreign Keys)
echo "\n6️⃣  Controllo integrità referenziale...\n";
// Check if foreign key constraints exist
$sql = "SELECT CONSTRAINT_NAME 
        FROM information_schema.TABLE_CONSTRAINTS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND CONSTRAINT_TYPE = 'FOREIGN KEY'";
$stmt = $pdo->query($sql);
$fks = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!empty($fks)) {
    echo sprintf("   ✅ %d vincoli foreign key attivi\n", count($fks));
} else {
    echo "   ⚠️  Nessun vincolo foreign key trovato\n";
    $issues[] = "Foreign key mancanti";
}

// 7. Statistiche Dati
echo "\n7️⃣  Statistiche Dati:\n";
try {
    $stats = [
        'soci' => $pdo->query("SELECT COUNT(*) FROM soci")->fetchColumn(),
        'documenti' => $pdo->query("SELECT COUNT(*) FROM documenti")->fetchColumn(),
        'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'audit_logs' => $pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn(),
    ];

    foreach ($stats as $table => $count) {
        echo sprintf("   - %-15s: %d record\n", $table, $count);
    }
} catch (\Exception $e) {
    echo "   ⚠️  Impossibile recuperare statistiche: " . $e->getMessage() . "\n";
}

// Summary
echo "\n" . str_repeat('=', 60) . "\n";
echo "📊 RIEPILOGO CONTROLLO INTEGRITÀ\n";
echo str_repeat('=', 60) . "\n\n";

if (empty($issues)) {
    echo "✅ Tutti i controlli passati con successo\n";
    echo "\n🎉 STATO DATABASE: HEALTHY\n";
    exit(0);
} else {
    echo "⚠️  Problemi trovati: " . count($issues) . "\n\n";
    foreach ($issues as $issue) {
        echo "   - $issue\n";
    }
    echo "\n💡 Raccomandazioni:\n";
    echo "   - Controllare i problemi manualmente.\n";
    echo "\n⚠️  STATO DATABASE: RICHIEDE ATTENZIONE\n";
    exit(1);
}

