<?php

namespace Tests\Unit;

use FratellanzaMilitare\Service\RegistrationService;
use FratellanzaMilitare\GestioneSoci\SocioRepository;
use FratellanzaMilitare\Service\ValidationService;
use FratellanzaMilitare\Service\PdfGenerationService;
use FratellanzaMilitare\Service\EmailServiceInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use FratellanzaMilitare\GestioneSoci\Socio;

class CivilianRegistrationTest extends TestCase
{
    private $repoMock;
    private $validatorMock;
    private $pdfMock;
    private $emailMock;
    private $loggerMock;
    private $service;

    protected function setUp(): void
    {
        $this->repoMock = $this->createMock(SocioRepository::class);
        $this->validatorMock = $this->createMock(ValidationService::class);
        $this->pdfMock = $this->createMock(PdfGenerationService::class);
        $this->emailMock = $this->createMock(EmailServiceInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->service = new RegistrationService(
            $this->repoMock,
            $this->validatorMock,
            $this->pdfMock,
            $this->emailMock,
            $this->loggerMock
        );
    }

    public function testRegisterCivilianIgnoresMilitaryData()
    {
        // Arrange
        $payload = [
            'codice_fiscale' => 'RSSMRA90A01H501Z',
            'nome' => 'MARIA',
            'cognome' => 'ROSSI',
            'data_nascita' => '1990-01-01',
            'sesso' => 'F',
            'luogo_nascita' => 'ROMA', // Italian
            'tipo_profilo' => 'CIVILE',
            // No Military Data provided
            'grado' => '',
            'corpo_appartenenza' => '',
        ];

        $this->validatorMock->method('isValidCodiceFiscale')->willReturn(true);
        $this->repoMock->method('findByCodiceFiscale')->willReturn(null);

        // Assert
        $this->repoMock->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Socio $socio) {
                return $socio->Grado === null &&
                    $socio->CorpoAppartenenza === null &&
                    $socio->DatiPersonali->Nome === 'MARIA';
            }));

        // Act
        $this->service->registerNewMember($payload);
    }

    public function testRegisterForeignBornUser()
    {
        // Arrange
        $payload = [
            'codice_fiscale' => 'BNCMRA80A01Z404M', // Example CF for foreign birth
            'nome' => 'MARCO',
            'cognome' => 'BIANCHI',
            'data_nascita' => '1980-01-01',
            'sesso' => 'M',
            'luogo_nascita' => 'LONDRA', // Foreign State/City
            'tipo_profilo' => 'CIVILE'
        ];

        $this->validatorMock->method('isValidCodiceFiscale')->willReturn(true);
        $this->repoMock->method('findByCodiceFiscale')->willReturn(null);

        // Assert
        $this->repoMock->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Socio $socio) {
                return $socio->DatiPersonali->LuogoNascita === 'LONDRA';
            }));

        // Act
        $this->service->registerNewMember($payload);
    }
}
