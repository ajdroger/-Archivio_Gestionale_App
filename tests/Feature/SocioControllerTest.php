<?php

use FratellanzaMilitare\Controller\SocioController;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDOSocioRepository;
use FratellanzaMilitare\GestioneSoci\Socio;
use FratellanzaMilitare\GestioneSoci\DatiAnagrafici;
use FratellanzaMilitare\Enum\StatoIscrizione;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

test('socio list renders', function () {
    /** @var \Tests\TestCase $this */
    $mustache = $this->createMock(Mustache_Engine::class);
    $mustache->expects($this->once())
        ->method('render')
        ->willReturn('List HTML');

    $repo = $this->createMock(PDOSocioRepository::class);

    // Mock socio object
    $socio = new Socio();
    $socio->CodiceFiscale = "CF123";
    $socio->Matricola = "M123";
    $socio->Stato = StatoIscrizione::ATTIVO;
    $socio->DatiPersonali = new DatiAnagrafici();
    $socio->DatiPersonali->Nome = "Test";
    $socio->DatiPersonali->Cognome = "User";
    $socio->DatiPersonali->DataNascita = new DateTime();
    $socio->DatiPersonali->Indirizzo = "Via Test";
    $socio->DatiPersonali->Email = "test@example.com";
    $socio->DatiPersonali->Telefono = "123456789";

    $repo->expects($this->once())
        ->method('findAll')
        ->willReturn([$socio]);

    $logger = $this->createMock(LoggerInterface::class);
    $validator = new \FratellanzaMilitare\Service\ValidationService();

    $registrationService = $this->createMock(\FratellanzaMilitare\Service\RegistrationService::class);
    $controller = new SocioController($mustache, $repo, $logger, $validator, $registrationService);

    $request = $this->withRouting((new ServerRequestFactory())->createServerRequest('GET', '/soci')
        ->withAttribute('csrf_name', 'csrf')
        ->withAttribute('csrf_value', 'val'));
    $response = (new ResponseFactory())->createResponse();

    $result = $controller->list($request, $response);

    expect($result->getStatusCode())->toBe(200);
});

test('socio create stores data', function () {
    /** @var \Tests\TestCase $this */
    $mustache = $this->createMock(Mustache_Engine::class);

    $repo = $this->createMock(PDOSocioRepository::class);
    // Delegate to Service, so controller doesn't call save directly on repo

    $logger = $this->createMock(LoggerInterface::class);

    $validator = new \FratellanzaMilitare\Service\ValidationService();

    // Mock RegistrationService with expectation
    $registrationService = $this->createMock(\FratellanzaMilitare\Service\RegistrationService::class);
    // Expect registerNewMember to be called once
    $registrationService->expects($this->once())
        ->method('registerNewMember')
        ->willReturnCallback(function ($data) {
            // Mock returning a dummy Socio
            $s = new Socio();
            $s->CodiceFiscale = $data['codice_fiscale'];
            return $s;
        });

    $controller = new SocioController($mustache, $repo, $logger, $validator, $registrationService);

    $data = [
        'codice_fiscale' => 'NEWCF12345678901',
        'nome' => 'New',
        'cognome' => 'User',
        'pagamento_effettuato' => '1'
    ];

    $request = $this->withRouting((new ServerRequestFactory())->createServerRequest('POST', '/soci')
        ->withParsedBody($data));
    $response = (new ResponseFactory())->createResponse();

    $result = $controller->store($request, $response);

    expect($result->getStatusCode())->toBe(302);
    expect($result->getHeaderLine('Location'))->toContain('/soci');
});
