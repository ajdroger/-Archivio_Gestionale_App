<?php

namespace Tests\Feature\Soci;

use MCAG\Controller\Anagrafica\Soci\DetailController;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;
use MCAG\GestioneSoci\Socio;
use MCAG\GestioneSoci\DatiAnagrafici;
use MCAG\Enum\StatoIscrizione;

class DossierIntelligenceTest extends TestCase
{
    private $controller;
    private $requestFactory;
    private $responseFactory;

    protected function setUp(): void
    {
        // Mock Dependencies
        $mustache = $this->createMock(\Mustache_Engine::class);
        $repo = $this->createMock(\MCAG\InfrastrutturaIT\Persistence\PDOSocioRepository::class);
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);

        // Real Socio Object
        $socio = new Socio();
        $socio->CodiceFiscale = 'RSSMRA80A01H501U';
        $socio->Matricola = '12345';
        $socio->DocumentiAssociati = [];

        $dati = new DatiAnagrafici();
        $dati->Nome = 'Mario';
        $dati->Cognome = 'Rossi';
        $dati->Email = 'mario@test.it';
        $dati->Telefono = '3331234567';
        $dati->Indirizzo = 'Via Roma 1';
        $dati->DataNascita = new \DateTime('1980-01-01');

        $socio->DatiPersonali = $dati;
        $socio->Stato = StatoIscrizione::ATTIVO;
        $socio->DataArruolamento = new \DateTime('2024-01-01');

        // Repo behavior
        $repo->method('findByCodiceFiscale')->willReturn($socio);

        // Mustache behavior - Capture view data
        $mustache->method('render')->willReturnCallback(function ($template, $data) {
            return json_encode($data);
        });

        $this->controller = new DetailController($mustache, $repo, $logger);
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
    }

    public function testDossierContainsIntelligenceData()
    {
        // Arrange
        $request = $this->requestFactory->createServerRequest('GET', '/soci/RSSMRA80A01H501U');
        $request = $request->withAttribute('csrf_name', 'csrf_test')->withAttribute('csrf_value', '12345');
        $response = $this->responseFactory->createResponse();

        // Act
        $result = $this->controller->detail($request, $response, ['cf' => 'RSSMRA80A01H501U']);
        $body = (string) $result->getBody();
        $data = json_decode($body, true);

        // Assert
        $this->assertArrayHasKey('intelligence', $data, "Intelligence key missing from Dossier View");

        // 1. Service History
        $this->assertArrayHasKey('service_history', $data['intelligence']);
        $this->assertNotEmpty($data['intelligence']['service_history']);
        $this->assertEquals('Reclutamento', $data['intelligence']['service_history'][0]['title']);

        // 2. Awards (Gamification)
        $this->assertArrayHasKey('awards', $data['intelligence']);
        $this->assertNotEmpty($data['intelligence']['awards']);
        $this->assertEquals('Servizio Attivo', $data['intelligence']['awards'][0]['name']);

        // 3. Access Log (Traceability)
        $this->assertArrayHasKey('access_log', $data['intelligence']);
        $this->assertCount(3, $data['intelligence']['access_log']);
    }

    /*
    public function testDossierAwardsChangeForInactiveSocio()
    {
        // Re-setup with Inactive Socio
        $repo = $this->createMock(\MCAG\InfrastrutturaIT\Persistence\PDOSocioRepository::class);

        $socio = new Socio();
        $socio->CodiceFiscale = 'INACTIVE123';
        $socio->DocumentiAssociati = [];

        $dati = new DatiAnagrafici();
        $dati->Nome = 'Luigi';
        $dati->Cognome = 'Bianchi';
        $dati->Email = 'l@b.it';
        $dati->Indirizzo = 'Via Po';
        $dati->DataNascita = new \DateTime('1990-01-01');

        $socio->DatiPersonali = $dati;
        $socio->Stato = StatoIscrizione::DECADUTO; // Use valid Enum case
        $socio->DataArruolamento = null;

        $repo->method('findByCodiceFiscale')->willReturn($socio);

        $mustache = $this->createMock(\Mustache_Engine::class);
        $mustache->method('render')->willReturnCallback(function($t, $d) { return json_encode($d); });

        $controller = new DetailController($mustache, $repo, $this->createMock(\Psr\Log\LoggerInterface::class));

        $req = $this->requestFactory->createServerRequest('GET', '/');
        $res = $this->responseFactory->createResponse();

        $result = $controller->detail($req, $res, ['cf' => 'INACTIVE123']);
        $data = json_decode((string)$result->getBody(), true);

        // Assert Award is "Archivio Storico"
        $this->assertEquals('Archivio Storico', $data['intelligence']['awards'][0]['name']);
    }
    */
}
