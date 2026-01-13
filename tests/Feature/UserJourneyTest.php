<?php

use MCAG\Controller\Anagrafica\Soci\ListController;
use MCAG\Controller\Anagrafica\Soci\DetailController;
use MCAG\Controller\Anagrafica\Soci\PersistenceController;
use MCAG\GestioneSoci\Socio;
use MCAG\Enum\StatoIscrizione;
use MCAG\GestioneSoci\ModuloIscrizione;
use MCAG\InfrastrutturaIT\Persistence\PDOSocioRepository;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;
use Psr\Log\NullLogger;

test('User Journey: Complete Lifecycle of a Socio via Modulo Anagrafica', function () {
    /** @var \Tests\TestCase $this */

    $mustache = new Mustache_Engine([
        'loader' => new Mustache_Loader_ArrayLoader([
            'socio_list' => 'List',
            'socio_detail' => 'Detail {{socio.nome}}',
            'socio_create' => 'Create',
            'socio_edit' => 'Edit'
        ])
    ]);

    $repo = new PDOSocioRepository($this->db);
    $logger = new NullLogger();
    $validator = new \MCAG\Service\ValidationService();

    $pdfService = new \MCAG\Service\PdfGenerationService();
    $emailService = new \MCAG\Service\FileEmailService(__DIR__ . '/../../var/logs/tests/test_journey_emails.txt');
    $registrationService = new \MCAG\Service\RegistrationService($repo, $validator, $pdfService, $emailService, $logger);

    $listCtrl = new ListController($mustache, $repo);
    $detailCtrl = new DetailController($mustache, $repo, $logger);
    $persistenceCtrl = new PersistenceController($mustache, $repo, $logger, $validator, $registrationService);

    // 1. CREAZIONE
    $newSocioData = [
        'codice_fiscale' => 'RSSMRA80A01H501Z',
        'nome' => 'Test',
        'cognome' => 'User',
        'data_nascita' => '1990-01-01',
        'indirizzo' => 'Via Test 123',
        'email' => 'test.user@example.com',
        'telefono' => '3330000000',
        'matricola' => 'M9999',
        'pagamento_effettuato' => '1'
    ];

    $request = $this->withRouting((new ServerRequestFactory())->createServerRequest('POST', '/soci/salva')
        ->withParsedBody($newSocioData));
    $response = (new ResponseFactory())->createResponse();

    $response = $persistenceCtrl->store($request, $response);

    expect($response->getStatusCode())->toBe(302);
    expect($response->getHeaderLine('Location'))->toContain('/soci');

    // 2. VERIFICA POPOLAMENTO DB
    $socio = $repo->findByCodiceFiscale('RSSMRA80A01H501Z');
    expect($socio)->not->toBeNull();
    expect($socio->DatiPersonali->Nome)->toBe('TEST');
    expect($socio->Stato)->toBe(StatoIscrizione::ATTIVO);

    // 3. DETTAGLIO
    $request = $this->withRouting((new ServerRequestFactory())->createServerRequest('GET', '/soci/RSSMRA80A01H501Z'));
    $response = (new ResponseFactory())->createResponse();
    $response = $detailCtrl->detail($request, $response, ['cf' => 'RSSMRA80A01H501Z']);

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
    $request = $this->withRouting((new ServerRequestFactory())->createServerRequest('POST', '/soci/RSSMRA80A01H501Z/aggiorna')
        ->withParsedBody($updateData));
    $response = (new ResponseFactory())->createResponse();

    $response = $persistenceCtrl->update($request, $response, ['cf' => 'RSSMRA80A01H501Z']);
    expect($response->getStatusCode())->toBe(302);

    // 5. CANCELLAZIONE
    $request = $this->withRouting((new ServerRequestFactory())->createServerRequest('POST', '/soci/RSSMRA80A01H501Z/elimina'));
    $response = (new ResponseFactory())->createResponse();

    $response = $persistenceCtrl->delete($request, $response, ['cf' => 'RSSMRA80A01H501Z']);
    expect($response->getStatusCode())->toBe(302);

    expect($repo->findByCodiceFiscale('RSSMRA80A01H501Z'))->toBeNull();
});
