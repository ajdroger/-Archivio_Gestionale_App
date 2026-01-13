<?php

use MCAG\Service\RegistrationService;
use MCAG\Service\ValidationService;
use MCAG\Service\PdfGenerationService;
use MCAG\InfrastrutturaIT\Persistence\PDOSocioRepository;
use Psr\Log\NullLogger;

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    // Ensure clean state
    if ($this->db) {
        $this->db->exec("DELETE FROM soci WHERE codice_fiscale IN ('ABCDEF01A01A001A', 'BCDEFG02B02B002B', 'CDEFGH03C03C003C')");
    }
});

afterEach(function () {
    /** @var \Tests\TestCase $this */
    if ($this->db) {
        $this->db->exec("DELETE FROM soci WHERE codice_fiscale IN ('ABCDEF01A01A001A', 'BCDEFG02B02B002B', 'CDEFGH03C03C003C')");
    }
});

test('registrazione fallisce se utente esiste gia', function () {
    /** @var \Tests\TestCase $this */
    $repo = new PDOSocioRepository($this->db);
    $service = new RegistrationService($repo, new ValidationService(), new PdfGenerationService(), new \MCAG\Service\FileEmailService(__DIR__ . '/../../var/logs/tests/test_pest_emails.txt'), new NullLogger());

    $data = [
        'nome' => 'Mario',
        'cognome' => 'Rossi',
        'codice_fiscale' => 'ABCDEF01A01A001A',
        'data_nascita' => '1980-01-01',
        'indirizzo' => 'Via Test',
        'email' => 'dup@test.com',
        'sesso' => 'M',
        'luogo_nascita' => 'Roma',
        'pagamento_effettuato' => '1'
    ];

    // Prima registrazione OK
    $service->registerNewMember($data);

    // Seconda registrazione DEVE fallire
    expect(fn() => $service->registerNewMember($data))->toThrow(Exception::class, "Utente già registrato");
});

test('pdf generato solo se pagamento effettuato', function () {
    /** @var \Tests\TestCase $this */
    $repo = new PDOSocioRepository($this->db);
    // Mock PDF Service using Anonymous Class to avoid Mocking issues
    $pdfService = new class () extends PdfGenerationService {
        public function generateRegistrationReceipt(\MCAG\GestioneSoci\Socio $socio, float $amount, int $year): string
        {
            throw new Exception("Unexpected call to generateRegistrationReceipt");
        }
    };

    $service = new RegistrationService($repo, new ValidationService(), $pdfService, new \MCAG\Service\FileEmailService(__DIR__ . '/../../logs/tests/test_pest_emails.txt'), new NullLogger());

    $data = [
        'nome' => 'Luigi',
        'cognome' => 'Bianchi',
        'codice_fiscale' => 'BCDEFG02B02B002B',
        'data_nascita' => '1990-01-01',
        'pagamento_effettuato' => '0' // NO PAGAMENTO
    ];

    $socio = $service->registerNewMember($data);
    expect($socio->DocumentiAssociati)->toBeEmpty();
});

test('pdf generato se pagamento presente', function () {
    /** @var \Tests\TestCase $this */
    $repo = new PDOSocioRepository($this->db);

    // Real PDF Service for integration check (or Mock implementation if we don't want real file IO)
    // Let's use Real to verify integration
    $pdfService = new PdfGenerationService();

    $service = new RegistrationService($repo, new ValidationService(), $pdfService, new \MCAG\Service\FileEmailService(__DIR__ . '/../../logs/tests/test_pest_emails.txt'), new NullLogger());

    $data = [
        'nome' => 'Anna',
        'cognome' => 'Verdi',
        'codice_fiscale' => 'CDEFGH03C03C003C',
        'data_nascita' => '1995-01-01',
        'pagamento_effettuato' => '1' // SI PAGAMENTO
    ];

    $socio = $service->registerNewMember($data);
    expect($socio->DocumentiAssociati)->toHaveCount(1);
    expect($socio->DocumentiAssociati[0]->NomeFile)->toContain('.pdf');

    // Cleanup file
    $doc = $socio->DocumentiAssociati[0];
    $path = __DIR__ . '/../../storage/uploads/' . $doc->IdUnivoco . '_' . $doc->NomeFile;
    if (file_exists($path)) {
        unlink($path);
    }
});
