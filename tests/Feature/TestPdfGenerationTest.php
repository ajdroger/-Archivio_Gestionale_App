<?php

use MCAG\Service\ValidationService;
use MCAG\InfrastrutturaIT\Persistence\PDOSocioRepository;
use MCAG\Controller\Anagrafica\Soci\PersistenceController;

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->repo = new PDOSocioRepository($this->db);
});

afterEach(function () {
    /** @var \Tests\TestCase $this */
    $this->repo->delete("LNCGNN80A01H501Z");

    $files = glob(__DIR__ . '/../../storage/uploads/*_iscrizione_auto_LNCGNN80A01H501Z.pdf');
    foreach ($files as $file) {
        unlink($file);
    }
});

test('it generates physical pdf on socio creation with payment', function () {
    /** @var \Tests\TestCase $this */

    $request = (new \Slim\Psr7\Factory\ServerRequestFactory())->createServerRequest('POST', '/soci/salva')
        ->withParsedBody([
            'codice_fiscale' => 'LNCGNN80A01H501Z',
            'nome' => 'AUTO',
            'cognome' => 'GEN',
            'matricola' => 'M_AUTO_PDF',
            'data_nascita' => '1990-01-01',
            'pagamento_effettuato' => '1'
        ])
        ->withAttribute('csrf_name', 'csrf_mock')
        ->withAttribute('csrf_value', 'csrf_mock');

    $request = $this->withRouting($request);

    $mustache = $this->createMock(Mustache_Engine::class);
    $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
    $validator = new ValidationService();

    $controller = new PersistenceController(
        $mustache,
        $this->repo,
        $logger,
        $validator,
        new \MCAG\Service\RegistrationService(
            $this->repo,
            $validator,
            new \MCAG\Service\PdfGenerationService(),
            new \MCAG\Service\FileEmailService(__DIR__ . '/../../logs/test_pdf_emails.txt'),
            $logger
        )
    );

    $response = new \Slim\Psr7\Response();
    $result = $controller->store($request, $response);

    expect($result->getStatusCode())->toBe(302);

    $socio = $this->repo->findByCodiceFiscale('LNCGNN80A01H501Z');
    expect($socio)->not->toBeNull();
    expect($socio->DocumentiAssociati)->toHaveCount(1);

    $doc = $socio->DocumentiAssociati[0];
    expect($doc->NomeFile)->toContain('iscrizione_auto_LNCGNN80A01H501Z.pdf');

    $filePath = __DIR__ . '/../../storage/uploads/' . $doc->IdUnivoco . '_' . $doc->NomeFile;
    expect(file_exists($filePath))->toBeTrue();
    expect(filesize($filePath))->toBeGreaterThan(0);
});
