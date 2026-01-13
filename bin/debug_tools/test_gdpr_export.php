<?php

require __DIR__ . '/../../vendor/autoload.php';

use MCAG\InfrastrutturaIT\Persistence\DatabaseConnection;
use MCAG\InfrastrutturaIT\Persistence\PDOSocioRepository;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "🧪 Testing GDPR Data Export...\n\n";

try {
    $pdo = DatabaseConnection::getConnection();
    $repo = new PDOSocioRepository($pdo);

    // Get first socio from database
    $soci = $repo->findAll();
    if (empty($soci)) {
        echo "❌ No soci found in database\n";
        exit(1);
    }

    $testSocio = $soci[0];
    echo "Testing with: {$testSocio->DatiPersonali->Nome} {$testSocio->DatiPersonali->Cognome}\n";
    echo "CF: {$testSocio->CodiceFiscale}\n\n";

    $exportData = $repo->exportGDPRData($testSocio->CodiceFiscale);

    echo "📊 GDPR Export Result:\n";
    echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "\n\n";

    // Verify structure
    $requiredKeys = ['export_date', 'data_subject', 'membership_data', 'documents', 'consents'];
    $missing = array_diff($requiredKeys, array_keys($exportData));

    if (empty($missing)) {
        echo "✅ All required keys present\n";
    } else {
        echo "❌ Missing keys: " . implode(', ', $missing) . "\n";
        exit(1);
    }

    // Verify dates are included
    if (isset($exportData['membership_data']['data_iscrizione'])) {
        echo "✅ data_iscrizione included: {$exportData['membership_data']['data_iscrizione']}\n";
    } else {
        echo "⚠️ data_iscrizione missing (might be NULL in DB)\n";
    }

    if (isset($exportData['membership_data']['data_scadenza'])) {
        echo "✅ data_scadenza included: {$exportData['membership_data']['data_scadenza']}\n";
    } else {
        echo "⚠️ data_scadenza missing (might be NULL in DB)\n";
    }

    echo "\n✅ GDPR Export Test PASSED\n";

} catch (Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
    exit(1);
}

