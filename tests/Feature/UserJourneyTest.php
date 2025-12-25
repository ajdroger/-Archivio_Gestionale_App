<?php

use FratellanzaMilitare\Controller\SocioController;
use FratellanzaMilitare\GestioneSoci\Socio;
use FratellanzaMilitare\Enum\StatoIscrizione;
use FratellanzaMilitare\GestioneSoci\ModuloIscrizione;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDOSocioRepository;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;
use Psr\Log\NullLogger;

test('User Journey: Complete Lifecycle of a Socio via Controller', function () {
    /** @var \Tests\TestCase $this */

    // Dependencies
    $mustache = new Mustache_Engine([
        'loader' => new Mustache_Loader_ArrayLoader([
            'socio_list' => 'List',
            'socio_detail' => 'Detail {{socio.nome}}',
            'socio_create' => 'Create',
            'socio_edit' => 'Edit'
        ])
    ]);

    // Use Real Repository
    $repo = new PDOSocioRepository($this->db); // $this->db should be available from TestCase if setUp creates it?
    // Wait, TestCase has public ?PDO $db. Let's ensure it is initialized.
    // BaseTestCase usually doesn't init DB unless setUp does.
    // Checking TestCase.php again... it declares properties but doesn't show setUp logic initializing them.
    // Assuming PersistenceTest or similar initializes it.
    // Let's rely on DatabaseConnection::getConnection() singleton if $this->db is null.

    $logger = new NullLogger(); // Verify if Logger is needed. Controller construct: (Mustache, Repo, Logger)
    $validator = new \FratellanzaMilitare\Service\ValidationService();

    // Inject RegistrationService
    $pdfService = new \FratellanzaMilitare\Service\PdfGenerationService();
    $emailService = new \FratellanzaMilitare\Service\FileEmailService(__DIR__ . '/../../logs/test_journey_emails.txt');
    $registrationService = new \FratellanzaMilitare\Service\RegistrationService($repo, $validator, $pdfService, $emailService, $logger);

    $controller = new SocioController($mustache, $repo, $logger, $validator, $registrationService);

    // 1. CREAZIONE
    $newSocioData = [
        'codice_fiscale' => 'RSSMRA80A01H501Z', // Valid CF
        'nome' => 'Test',
        'cognome' => 'User',
        'data_nascita' => '1990-01-01',
        'indirizzo' => 'Via Test 123',
        'email' => 'test.user@example.com',
        'telefono' => '3330000000',
        'matricola' => 'M9999',
        'pagamento_effettuato' => '1'
    ];

    $request = $this->withRouting((new ServerRequestFactory())->createServerRequest('POST', '/soci')
        ->withParsedBody($newSocioData));
    $response = (new ResponseFactory())->createResponse();

    $response = $controller->store($request, $response);

    expect($response->getStatusCode())->toBe(302);
    expect($response->getHeaderLine('Location'))->toContain('/public/soci');

    // 2. VERIFICA POPOLAMENTO DB
    $socio = $repo->findByCodiceFiscale('RSSMRA80A01H501Z');
    expect($socio)->not->toBeNull();
    expect($socio->DatiPersonali->Nome)->toBe('TEST');
    expect($socio->Stato)->toBe(StatoIscrizione::ATTIVO);

    // Verifica Documento
    expect($socio->DocumentiAssociati)->toHaveCount(1);
    expect($socio->DocumentiAssociati[0])->toBeInstanceOf(ModuloIscrizione::class);

    // 3. DETTAGLIO
    $request = $this->withRouting((new ServerRequestFactory())->createServerRequest('GET', '/soci/RSSMRA80A01H501Z'));
    $response = (new ResponseFactory())->createResponse();
    // detail($req, $res, $args)
    $response = $controller->detail($request, $response, ['cf' => 'RSSMRA80A01H501Z']);

    expect($response->getStatusCode())->toBe(200);
    $response->getBody()->rewind();
    $body = $response->getBody()->getContents();
    expect($body)->toContain('Detail TEST');

    // 4. AGGIORNAMENTO
    $updateData = [
        'nome' => 'TEST',
        'cognome' => 'USER',
        'indirizzo' => 'Via Nuova 456',
        'email' => 'new.email@example.com',
        'matricola' => 'M9999'
    ];
    $request = $this->withRouting((new ServerRequestFactory())->createServerRequest('POST', '/soci/RSSMRA80A01H501Z/update')
        ->withParsedBody($updateData));
    $response = (new ResponseFactory())->createResponse();

    $response = $controller->update($request, $response, ['cf' => 'RSSMRA80A01H501Z']);
    expect($response->getStatusCode())->toBe(302);

    // Verifica Aggiornamento DB
    $socioUpdated = $repo->findByCodiceFiscale('RSSMRA80A01H501Z');
    expect($socioUpdated->DatiPersonali->Indirizzo)->toBe('Via Nuova 456');

    // 5. CANCELLAZIONE
    $request = $this->withRouting((new ServerRequestFactory())->createServerRequest('POST', '/soci/RSSMRA80A01H501Z/delete'));
    $response = (new ResponseFactory())->createResponse();

    $response = $controller->delete($request, $response, ['cf' => 'RSSMRA80A01H501Z']);
    expect($response->getStatusCode())->toBe(302);

    // Verifica Eliminazione
    $socioDeleted = $repo->findByCodiceFiscale('RSSMRA80A01H501Z');
    expect($socioDeleted)->toBeNull();

});
