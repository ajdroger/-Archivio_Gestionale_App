<?php

use MCAG\Service\RegistrationService;
use MCAG\Service\ValidationService;
use MCAG\Service\PdfGenerationService;
use MCAG\InfrastrutturaIT\Persistence\PDOSocioRepository;
use MCAG\InfrastrutturaIT\Persistence\DatabaseConnection;
use MCAG\Service\FileEmailService;
use Monolog\Logger;

// Shared setup for this file
beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->pdo = DatabaseConnection::getConnection();
    // Cleanup before start
    $this->pdo->exec("DELETE FROM soci WHERE codice_fiscale = 'RSSMRA80A01H501Z'");
    $this->pdo->exec("DELETE FROM soci WHERE codice_fiscale = 'TEST_REG_SERVICE_CF'");
});

afterEach(function () {
    /** @var \Tests\TestCase $this */
    // Cleanup after end
    if (isset($this->pdo)) {
        $this->pdo->exec("DELETE FROM soci WHERE codice_fiscale = 'RSSMRA80A01H501Z'");
        $this->pdo->exec("DELETE FROM soci WHERE codice_fiscale = 'TEST_REG_SERVICE_CF'");
    }
    // Cleanup Files if tracked? 
    // We'll handle file cleanup inside the test for specific created files, or here if we track them.
});

test('Integration: Registration Service Register New Member Flow', function () {
    /** @var \Tests\TestCase $this */
    // 1. Setup Dependencies
    $logger = new Logger('test_integration');
    $repo = new PDOSocioRepository($this->pdo);
    $validator = new ValidationService();
    $pdfService = new PdfGenerationService();
    $emailService = new FileEmailService(__DIR__ . '/../../var/logs/tests/test_integration_emails.txt');

    $service = new RegistrationService(
        $repo,
        $validator,
        $pdfService,
        $emailService,
        $logger
    );

    // 2. Data
    $data = [
        'nome' => 'Mario',
        'cognome' => 'Rossi',
        'codice_fiscale' => 'RSSMRA80A01H501Z',
        'data_nascita' => '1980-01-01',
        'indirizzo' => 'Via Roma 1',
        'email' => 'mario@test.com',
        'pagamento_effettuato' => '1' // Trigger PDF generation
    ];

    // 3. Execution
    $socio = $service->registerNewMember($data);

    // 4. Assertions
    expect($socio)->not->toBeNull()
        ->and($socio->CodiceFiscale)->toBe('RSSMRA80A01H501Z');

    // Verify DB Persistence
    $fetched = $repo->findByCodiceFiscale('RSSMRA80A01H501Z');
    expect($fetched)->not->toBeNull();
    expect($fetched->DatiPersonali->Nome)->toBe('MARIO');

    // Verify Document Generation
    $docs = $socio->DocumentiAssociati;
    expect($docs)->toBeArray();

    // Note: depending on PdfGeneration logic, docs might be empty if PDF generation failed silently or wasn't triggered. 
    // The original test assumed explicit success.
    if (count($docs) > 0) {
        $doc = $docs[0];
        $filePath = __DIR__ . '/../../storage/uploads/' . $doc->IdUnivoco . '_' . $doc->NomeFile;

        expect(file_exists($filePath))->toBeTrue("PDF Document should exist on disk");

        // Cleanup File
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    } else {
        // Fail if expected docs are missing
        $this->fail('No documents were generated for the registered member.');
    }

});
