<?php

namespace Tests\Unit;

use MCAG\Service\RegistrationService;
use MCAG\GestioneSoci\SocioRepository;
use MCAG\Service\ValidationService;
use MCAG\Service\PdfGenerationService;
use MCAG\Service\EmailServiceInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use MCAG\GestioneSoci\Socio;

class SocioProfileTest extends TestCase
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

    public function testRegisterNewMemberMapsAllProfileFields()
    {
        // Arrange
        $payload = [
            'codice_fiscale' => 'RSSMRA80A01H501Z',
            'nome' => 'Mario',
            'cognome' => 'Rossi',
            'data_nascita' => '1980-01-01',
            'sesso' => 'M',
            'luogo_nascita' => 'Roma',
            'stato_civile' => 'CONIUGATO/A',
            'indirizzo' => 'Via Roma 1',
            'email' => 'mario@test.it',
            'telefono' => '3331234567',
            'titolo_studio' => 'DIPLOMA',
            'professione' => 'IMPIEGATO',
            // Militare
            'grado' => 'CAPORALE',
            'corpo_appartenenza' => 'ALPINI',
            'data_arruolamento' => '2000-01-01',
            'data_congedo' => '2001-01-01',
            // Sanitario
            'gruppo_sanguigno' => '0 Rh+',
            'note_mediche' => 'Nessuna',
            'contatto_emergenza' => 'Maria 333999999'
        ];

        $this->validatorMock->method('isValidCodiceFiscale')->willReturn(true);
        $this->repoMock->method('findByCodiceFiscale')->willReturn(null);

        // Assert Repository Save is called with correctly populated Socio
        $this->repoMock->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Socio $socio) {
                return $socio->DatiPersonali->Nome === 'MARIO' &&
                    $socio->DatiPersonali->Sesso === 'M' &&
                    $socio->DatiPersonali->LuogoNascita === 'Roma' &&
                    $socio->Grado === 'CAPORALE' &&
                    $socio->CorpoAppartenenza === 'ALPINI' &&
                    $socio->GruppoSanguigno === '0 Rh+' &&
                    $socio->ContattoEmergenza === 'Maria 333999999';
            }));

        // Act
        $result = $this->service->registerNewMember($payload);

        // Assert Result
        $this->assertInstanceOf(Socio::class, $result);
        $this->assertEquals('ALPINI', $result->CorpoAppartenenza);
    }
}
