<?php

use FratellanzaMilitare\Service\ValidationService;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDOSocioRepository;

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->repo = new PDOSocioRepository();
});

afterEach(function () {
    /** @var \Tests\TestCase $this */
    $this->repo->delete("LNCGNN80A01H501Z");

    // Cleanup generated file (wildcard)
    $files = glob(__DIR__ . '/../../storage/uploads/*_iscrizione_auto_LNCGNN80A01H501Z.pdf');
    foreach ($files as $file) {
        unlink($file);
    }
});

test('it generates physical pdf on socio creation with payment', function () {
    /** @var \Tests\TestCase $this */

    // Mock Request
    /** @var \Psr\Http\Message\ServerRequestInterface&\PHPUnit\Framework\MockObject\MockObject $request */
    $request = $this->createMock(\Psr\Http\Message\ServerRequestInterface::class);

    // Apply routing first (which clones)
    $request = $this->withRouting($request);

    /** @var \Psr\Http\Message\ServerRequestInterface&\PHPUnit\Framework\MockObject\MockObject $request */

    // Then configure methods on the *returned* mock (if clone behavior allows) or ensure mock persists
    // Better strategy: Since mock objects don't behave like immutable PSR-7 messages by default (unless mocked to),
    // let's assume `withAttribute` returns `self`.

    // BUT: standard PHPUnit mocks return NULL for unconfigured methods, so withAttribute might return null if not mocked.
    // Let's configure the mock to return itself for withAttribute.
    $request->method('withAttribute')->willReturnSelf();

    $request->method('getParsedBody')->willReturn([
        'codice_fiscale' => 'LNCGNN80A01H501Z',
        'nome' => 'AUTO',
        'cognome' => 'GEN',
        'matricola' => 'M_AUTO_PDF',
        'data_nascita' => '1990-01-01',
        'pagamento_effettuato' => '1' // Trigger PDF generation
    ]);
    $request->method('getAttribute')->willReturn('csrf_mock');

    // Mock dependencies
    $mustache = $this->createMock(Mustache_Engine::class);
    $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
    $validator = new ValidationService();

    // Use Real Repo
    $controller = new \FratellanzaMilitare\Controller\SocioController(
        $mustache,
        $this->repo,
        $logger,
        $validator,
        new \FratellanzaMilitare\Service\RegistrationService(
            $this->repo,
            $validator,
            new \FratellanzaMilitare\Service\PdfGenerationService(),
            new \FratellanzaMilitare\Service\FileEmailService(__DIR__ . '/../../logs/test_pdf_emails.txt'),
            $logger
        )
    );

    // Mock Response
    $response = new \Slim\Psr7\Response();

    // Execute
    $request = $this->withRouting($request); // Helper from TestCase
    $result = $controller->store($request, $response);

    if ($result->getStatusCode() !== 302) {
        $result->getBody()->rewind();
        var_dump($result->getBody()->getContents());
    }

    expect($result->getStatusCode())->toBe(302);

    // Verify Socio Created
    $socio = $this->repo->findByCodiceFiscale('LNCGNN80A01H501Z');
    expect($socio)->not->toBeNull();
    expect($socio->DocumentiAssociati)->toHaveCount(1);

    $doc = $socio->DocumentiAssociati[0];
    expect($doc->NomeFile)->toContain('iscrizione_auto_LNCGNN80A01H501Z.pdf');

    // Verify Physical File Exists
    $filePath = __DIR__ . '/../../storage/uploads/' . $doc->IdUnivoco . '_' . $doc->NomeFile;
    expect(file_exists($filePath))->toBeTrue();
    expect(filesize($filePath))->toBeGreaterThan(0);
});
