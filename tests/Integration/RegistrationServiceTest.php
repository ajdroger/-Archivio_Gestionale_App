<?php

use FratellanzaMilitare\Service\RegistrationService;
use FratellanzaMilitare\Service\ValidationService;
use FratellanzaMilitare\Service\PdfGenerationService;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDOSocioRepository;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;
use Monolog\Logger;

require __DIR__ . '/../../vendor/autoload.php';

// Mock Logger
$logger = new Logger('test_integration');

// Dependencies
$pdo = DatabaseConnection::getConnection();
// CLEANUP
$pdo->exec("DELETE FROM soci WHERE codice_fiscale = 'TEST_REG_SERVICE_CF'");
$pdo->exec("DELETE FROM soci WHERE codice_fiscale = 'RSSMRA80A01H501Z'");

$repo = new PDOSocioRepository($pdo);
$validator = new ValidationService();
$pdfService = new PdfGenerationService();

$service = new RegistrationService(
    $repo,
    $validator,
    $pdfService,
    new \FratellanzaMilitare\Service\FileEmailService(__DIR__ . '/../../logs/test_integration_emails.txt'),
    $logger
);

echo "=== INTEGRATION TEST: RegistrationService ===\n";

// Data
$data = [
    'nome' => 'Mario',
    'cognome' => 'Rossi',
    'codice_fiscale' => 'RSSMRA80A01H501Z', // Valid Structure
    'data_nascita' => '1980-01-01',
    'indirizzo' => 'Via Roma 1',
    'email' => 'mario@test.com',
    'pagamento_effettuato' => '1' // Trigger PDF generation
];

try {
    // 2. Register
    $socio = $service->registerNewMember($data);
    echo "✓ Socio Registered: " . $socio->CodiceFiscale . "\n";

    // 3. Verify Persistence
    $fetched = $repo->findByCodiceFiscale('RSSMRA80A01H501Z');
    if ($fetched) {
        echo "✓ Persistence Verified: Found in DB.\n";
    } else {
        echo "✗ FAIL: Not found in DB.\n";
        exit(1);
    }

    // 4. Verify Document
    $docs = $socio->DocumentiAssociati;
    if (count($docs) > 0) {
        echo "✓ Document Verified: " . $docs[0]->NomeFile . "\n";
        // Check file existence
        $filePath = __DIR__ . '/../../storage/uploads/' . $docs[0]->IdUnivoco . '_' . $docs[0]->NomeFile;
        if (file_exists($filePath)) {
            echo "✓ File Verified: exists on disk.\n";
            // cleanup file
            unlink($filePath);
        } else {
            echo "✗ FAIL: File not found on disk at $filePath\n";
        }
    } else {
        echo "✗ FAIL: No document attached.\n";
    }

    // Cleanup DB
    $pdo->exec("DELETE FROM soci WHERE codice_fiscale = 'RSSMRA80A01H501Z'");
    echo "\n=== SUCCESS ===\n";

} catch (\Exception $e) {
    echo "\n=== ERROR ===\n" . $e->getMessage() . "\n";
    exit(1);
}
