<?php

use MCAG\Service\RegistrationService;
use MCAG\Service\ValidationService;
use MCAG\Service\PdfGenerationService;
use MCAG\InfrastrutturaIT\Persistence\PDOSocioRepository;
use MCAG\InfrastrutturaIT\Persistence\DatabaseConnection;
use Monolog\Logger;

require __DIR__ . '/../vendor/autoload.php';

// Mock Logger
$logger = new Logger('test');

// Dependencies
$pdo = DatabaseConnection::getConnection();
// CLEANUP
$pdo->exec("DELETE FROM soci WHERE codice_fiscale = 'TEST_REG_SERVICE_CF'");

$socioRepo = new PDOSocioRepository($pdo);
$validator = new ValidationService();
$pdfService = new PdfGenerationService();

$regService = new RegistrationService(
    $socioRepo,
    $validator,
    $pdfService,
    new \MCAG\Service\FileEmailService(__DIR__ . '/../../logs/test_emails.txt'),
    $logger
);

echo "Testing RegistrationService...\n";

// Data
$data = [
    'nome' => 'Mario',
    'cognome' => 'Rossi',
    // 'codice_fiscale' => 'TEST_REG_SERVICE_CF', // Removed for strict regex
    'codice_fiscale' => 'RSSMRA80A01H501Z', // Valid Structure
    'data_nascita' => '1980-01-01',
    'indirizzo' => 'Via Roma 1',
    'email' => 'mario@test.com',
    'pagamento_effettuato' => '1' // Trigger PDF generation
];

try {
    // 1. First Cleanup again to be sure
    $pdo->exec("DELETE FROM soci WHERE codice_fiscale = 'RSSMRA80A01H501Z'");

    // 2. Register
    $socio = $regService->registerNewMember($data);
    echo "Socio Registered: " . $socio->CodiceFiscale . "\n";

    // 3. Verify Persistence
    $fetched = $socioRepo->findByCodiceFiscale('RSSMRA80A01H501Z');
    if ($fetched) {
        echo "Persistence Verified: Found in DB.\n";
    } else {
        echo "FAIL: Not found in DB.\n";
        exit(1);
    }

    // 4. Verify Document
    $docs = $socio->DocumentiAssociati;
    if (count($docs) > 0) {
        echo "Document Verified: " . $docs[0]->NomeFile . "\n";
        // Check file existence
        $filePath = __DIR__ . '/../storage/uploads/' . $docs[0]->IdUnivoco . '_' . $docs[0]->NomeFile;
        if (file_exists($filePath)) {
            echo "File Verified: exists on disk.\n";
            // cleanup file
            unlink($filePath);
        } else {
            echo "FAIL: File not found on disk.\n";
        }
    } else {
        echo "FAIL: No document attached.\n";
    }

    // Cleanup DB
    $pdo->exec("DELETE FROM soci WHERE codice_fiscale = 'RSSMRA80A01H501Z'");
    echo "SUCCESS\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
