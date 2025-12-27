<?php

namespace FratellanzaMilitare\Service;

use FratellanzaMilitare\GestioneSoci\Socio;
use FratellanzaMilitare\GestioneSoci\DatiAnagrafici;
use FratellanzaMilitare\GestioneSoci\ModuloIscrizione;
use FratellanzaMilitare\GestioneSoci\SocioRepository;
use FratellanzaMilitare\Enum\StatoIscrizione;
use FratellanzaMilitare\Enum\StatoDocumento;
use FratellanzaMilitare\Service\ValidationService;
use FratellanzaMilitare\Service\PdfGenerationService;
use Psr\Log\LoggerInterface;

/**
 * Servizio per la gestione della registrazione nuovi soci.
 * 
 * Orchestra il flusso di iscrizione:
 * 1. Validazione dati (CF, Email).
 * 2. Verifica duplicati.
 * 3. Creazione Entità Socio.
 * 4. Gestione pagamento quote.
 * 5. Generazione PDF ricevuta.
 * 6. Invio email conferma.
 */
class RegistrationService
{
    private SocioRepository $repo;
    private ValidationService $validator;
    private PdfGenerationService $pdfService;
    private EmailServiceInterface $emailService;
    private LoggerInterface $logger;
    private string $uploadDir;

    public function __construct(
        SocioRepository $repo,
        ValidationService $validator,
        PdfGenerationService $pdfService,
        EmailServiceInterface $emailService,
        LoggerInterface $logger
    ) {
        $this->repo = $repo;
        $this->validator = $validator;
        $this->pdfService = $pdfService;
        $this->emailService = $emailService;
        $this->logger = $logger;
        $this->uploadDir = __DIR__ . '/../../storage/uploads/';
    }

    /**
     * Registra un nuovo socio nel sistema.
     * 
     * @param array $data Dati provenienti dal form (POST)
     * @return Socio L'entità Socio creata
     * @throws \InvalidArgumentException Se i dati non sono validi
     * @throws \Exception Se il socio esiste già
     */
    public function registerNewMember(array $data): Socio
    {
        // 1. Basic Validation
        if (empty($data['codice_fiscale']) || empty($data['nome']) || empty($data['cognome'])) {
            throw new \InvalidArgumentException("Dati obbligatori mancanti.");
        }

        // 2. Format Validation
        $cf = strtoupper($data['codice_fiscale']);
        if (!$this->validator->isValidCodiceFiscale($cf)) {
            throw new \InvalidArgumentException("Codice Fiscale non valido.");
        }

        // 3. Check Existence
        if ($this->repo->findByCodiceFiscale($cf)) {
            throw new \Exception("Utente già registrato");
        }

        // 4. Create Entity
        $socio = new Socio();
        $socio->CodiceFiscale = $cf;
        $socio->Matricola = !empty($data['matricola']) ? $data['matricola'] : 'M' . rand(10000, 99999);
        $socio->Stato = StatoIscrizione::ATTIVO;

        $dati = new DatiAnagrafici();
        $dati->Nome = strtoupper($data['nome']);
        $dati->Cognome = strtoupper($data['cognome']);
        $dati->DataNascita = !empty($data['data_nascita']) ? new \DateTime($data['data_nascita']) : new \DateTime('1900-01-01');
        $dati->Indirizzo = $data['indirizzo'] ?? '';
        $dati->Email = $data['email'] ?? '';
        $dati->Telefono = $data['telefono'] ?? '';
        $socio->DatiPersonali = $dati;

        // 4. Handle Payment (Optional)
        if (isset($data['pagamento_effettuato']) && $data['pagamento_effettuato'] == '1') {
            $this->processPayment($socio);
        }

        // 5. Persist
        $this->repo->save($socio);
        $this->logger->info("Nuovo socio registrato: {$cf}", ['matricola' => $socio->Matricola]);

        return $socio;
    }

    /**
     * Gestisce il processo di pagamento della quota associativa.
     * 
     * Crea un documento 'Copia Modulo Iscrizione', genera il PDF e invia notifica.
     */
    private function processPayment(Socio $socio): void
    {
        $year = 2025; // Could be dynamic
        $amount = 50.00;

        $iscrizione = new ModuloIscrizione();
        $iscrizione->IdUnivoco = uniqid();
        $iscrizione->NomeFile = 'iscrizione_auto_' . $socio->CodiceFiscale . '.pdf';
        $iscrizione->HashSHA256 = hash('sha256', $socio->CodiceFiscale . time());
        $iscrizione->Stato = StatoDocumento::VALIDATO;
        $iscrizione->DataCaricamento = new \DateTime();
        $iscrizione->AnnoSolare = $year;
        $iscrizione->QuotaVersata = $amount;
        $iscrizione->MetodoPagamento = 'SPORTELLO';

        $socio->aggiungiDocumento($iscrizione);

        // Generate PDF
        $pdfContent = $this->pdfService->generateRegistrationReceipt($socio, $amount, $year);

        // Save File
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
        $filePath = $this->uploadDir . $iscrizione->IdUnivoco . '_' . $iscrizione->NomeFile;
        file_put_contents($filePath, $pdfContent);

        $this->logger->info("Generata ricevuta PDF per: {$socio->CodiceFiscale}");

        // Send Email
        if (!empty($socio->DatiPersonali->Email)) {
            $subject = "Ricevuta Iscrizione " . $year . " - Fratellanza Militare";
            $body = "Gentile {$socio->DatiPersonali->Nome},<br><br>In allegato la ricevuta di iscrizione per l'anno $year.<br><br>Cordiali Saluti,<br>Segreteria.";

            $this->emailService->send(
                $socio->DatiPersonali->Email,
                $subject,
                $body,
                [$filePath]
            );
        }
    }
}
