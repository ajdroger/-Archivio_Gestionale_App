<?php

/**
 * Database Integrity Checker
 * Verifies referential integrity, orphaned records, and data consistency
 */

require __DIR__ . '/../vendor/autoload.php';

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "🔍 DATABASE INTEGRITY CHECKER\n";
echo str_repeat('=', 60) . "\n\n";

$pdo = DatabaseConnection::getConnection();
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
$issues = [];

// 1. Orphaned Documents (documents without soci)
echo "1️⃣  Checking for orphaned documents...\n";
$cfCol = ($driver === 'mysql') ? 'socio_cf' : 'codice_fiscale_socio';
$sql = "SELECT COUNT(*) as count 
        FROM documenti d 
        LEFT JOIN soci s ON d.$cfCol = s.codice_fiscale 
        WHERE s.codice_fiscale IS NULL";
$stmt = $pdo->query($sql);
$orphanedDocs = $stmt->fetchColumn();

if ($orphanedDocs > 0) {
    echo "   ⚠️  Found $orphanedDocs orphaned document(s)\n";
    $issues[] = "Orphaned documents: $orphanedDocs";

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
    echo "   ✅ No orphaned documents\n";
}

// 2. Missing Document Files
echo "\n2️⃣  Checking for missing document files...\n";
$stmt = $pdo->query("SELECT id, nome_file, percorso_file FROM documenti WHERE percorso_file IS NOT NULL");
$docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$missingFiles = 0;

foreach ($docs as $doc) {
    if ($doc['percorso_file'] && !file_exists($doc['percorso_file'])) {
        $missingFiles++;
        if ($missingFiles <= 5) {
            echo "   ⚠️  File missing: {$doc['nome_file']} (ID: {$doc['id']})\n";
        }
    }
}

if ($missingFiles > 0) {
    echo sprintf("   ⚠️  Total missing files: %d\n", $missingFiles);
    $issues[] = "Missing document files: $missingFiles";
} else {
    echo "   ✅ All document files exist\n";
}

// 3. Duplicate Codici Fiscali
echo "\n3️⃣  Checking for duplicate codici fiscali...\n";
$sql = "SELECT codice_fiscale, COUNT(*) as count 
        FROM soci 
        GROUP BY codice_fiscale 
        HAVING count > 1";
$stmt = $pdo->query($sql);
$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($duplicates)) {
    echo sprintf("   ⚠️  Found %d duplicate(s)\n", count($duplicates));
    foreach ($duplicates as $dup) {
        echo "      - CF: {$dup['codice_fiscale']} (appears {$dup['count']} times)\n";
    }
    $issues[] = "Duplicate codici fiscali: " . count($duplicates);
} else {
    echo "   ✅ No duplicate codici fiscali\n";
}

// 4. Invalid Email Formats
echo "\n4️⃣  Checking email formats...\n";
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
    echo sprintf("   ⚠️  Total invalid emails: %d\n", $invalidEmails);
    $issues[] = "Invalid email formats: $invalidEmails";
} else {
    echo "   ✅ All emails valid\n";
}

// 5. Null Required Fields
echo "\n5️⃣  Checking for NULL required fields...\n";
$requiredFields = ['nome', 'cognome', 'codice_fiscale', 'matricola'];
$nullFields = [];

foreach ($requiredFields as $field) {
    $sql = "SELECT COUNT(*) FROM soci WHERE $field IS NULL OR $field = ''";
    $count = $pdo->query($sql)->fetchColumn();
    if ($count > 0) {
        echo "   ⚠️  Field '$field' is NULL/empty in $count record(s)\n";
        $nullFields[] = "$field: $count";
    }
}

if (empty($nullFields)) {
    echo "   ✅ All required fields populated\n";
} else {
    $issues[] = "NULL required fields: " . implode(', ', $nullFields);
}

// 6. Referential Integrity (Foreign Keys)
echo "\n6️⃣  Checking referential integrity...\n";
if ($driver === 'mysql') {
    // Check if foreign key constraints exist
    $sql = "SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'";
    $stmt = $pdo->query($sql);
    $fks = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($fks)) {
        echo sprintf("   ✅ %d foreign key constraint(s) active\n", count($fks));
    } else {
        echo "   ⚠️  No foreign key constraints found\n";
        $issues[] = "Missing foreign key constraints";
    }
} else {
    echo "   ℹ️  Foreign key check not applicable for SQLite\n";
}

// 7. Data Statistics
echo "\n7️⃣  Data Statistics:\n";
$stats = [
    'soci' => $pdo->query("SELECT COUNT(*) FROM soci")->fetchColumn(),
    'documenti' => $pdo->query("SELECT COUNT(*) FROM documenti")->fetchColumn(),
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'audit_logs' => $pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn(),
];

foreach ($stats as $table => $count) {
    echo sprintf("   - %-15s: %d record(s)\n", $table, $count);
}

// Summary
echo "\n" . str_repeat('=', 60) . "\n";
echo "📊 INTEGRITY CHECK SUMMARY\n";
echo str_repeat('=', 60) . "\n\n";

if (empty($issues)) {
    echo "✅ All integrity checks passed\n";
    echo "\n🎉 DATABASE STATUS: HEALTHY\n";
    exit(0);
} else {
    echo "⚠️  Issues found: " . count($issues) . "\n\n";
    foreach ($issues as $issue) {
        echo "   - $issue\n";
    }
    echo "\n💡 Recommendations:\n";
    if (in_array('Orphaned documents', array_column($issues, 0))) {
        echo "   - Run: DELETE FROM documenti WHERE socio_cf NOT IN (SELECT codice_fiscale FROM soci)\n";
    }
    if (in_array('Duplicate codici fiscali', array_column($issues, 0))) {
        echo "   - Manually review and merge/delete duplicate records\n";
    }
    echo "\n⚠️  DATABASE STATUS: NEEDS ATTENTION\n";
    exit(1);
}
