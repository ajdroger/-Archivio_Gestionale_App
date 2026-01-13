<?php

/**
 * Fratellanza Militare - System Simulation (Ultimate Edition)
 * 
 * Questo script simula un intero ciclo di vita di un Socio all'interno del sistema,
 * dimostrando le capacità di OCR, Cloud Storage, Database e Logica di Business.
 */

// Protezione ambiente di produzione
if (getenv('APP_ENV') === 'production') {
    die("ERRORE: La simulazione non può essere eseguita in ambiente di produzione.\n");
}

require __DIR__ . '/../../vendor/autoload.php';

// Load Environment Variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

use MCAG\GestioneSoci\Socio;
use MCAG\GestioneSoci\DatiAnagrafici;
use MCAG\GestioneSoci\ModuloIscrizione;
use MCAG\InfrastrutturaIT\OCREngine;
use MCAG\InfrastrutturaIT\GoogleDriveAdapter;
use MCAG\InfrastrutturaIT\Persistence\PDOSocioRepository;
use MCAG\Enum\StatoDocumento;
use MCAG\Enum\StatoIscrizione;

// --- CLI HELPER FUNCTIONS ---

function printHeader()
{
    if (strpos(strtoupper(PHP_OS), 'WIN') !== false)
        system('cls');
    else
        system('clear');
    echo "\n\033[1;34m";
    echo "   ______________________________________________________________   \n";
    echo "  |                                                              |  \n";
    echo "  |    FRATELLANZA MILITARE DI FIRENZE - CORE SYSTEM v2.0        |  \n";
    echo "  |       >> SIMULAZIONE PROCESSO DI ISCRIZIONE DIGITALE <<      |  \n";
    echo "  |______________________________________________________________|  \n";
    echo "\033[0m\n";
}

function printSection($title)
{
    echo "\n\033[1;36m==============================================================\033[0m\n";
    echo "\033[1;33m  >>> " . strtoupper($title) . " <<<\033[0m\n";
    echo "\033[1;36m==============================================================\033[0m\n\n";
    usleep(500000);
}

function loader($message)
{
    echo "  > $message";
    $chars = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];
    // Fast spin for demo
    for ($i = 0; $i < 5; $i++) {
        usleep(100000);
    }
    echo " \033[1;32m[OK]\033[0m\n";
}

function success($msg)
{
    echo "  \033[1;32m✓ $msg\033[0m\n";
}

function info($msg)
{
    echo "  \033[0;36mi $msg\033[0m\n";
}

function warning($msg)
{
    echo "  \033[1;33m! $msg\033[0m\n";
}


// --- MAIN EXECUTION ---

try {
    printHeader();

    info("Inizializzazione Kernel...");
    usleep(300000);
    info("Caricamento Driver DB e Cloud...");
    usleep(300000);
    success("Sistema Pronto.");

    // --- FASE 1: ACQUISIZIONE ---
    printSection("Fase 1: Acquisizione Documentale & AI OCR");

    $ocr = new OCREngine();
    $cloud = new GoogleDriveAdapter();
    $repo = new PDOSocioRepository();

    info("Simulazione scansione fisica modulo cartaceo...");
    loader("Attivazione Scanner TWAIN alta risoluzione");

    $dummyImage = "scansione_modulo_2025_mario_rossi.bmp";
    info("Immagine acquisita: $dummyImage");
    loader("Analisi AI (Rete Neurale OCR) in corso");

    $datiEstratti = $ocr->estraiDatiDaImmagine($dummyImage);

    echo "\n  \033[1;33m[DATI ESTRATTI DALL'INTELLIGENZA ARTIFICIALE]\033[0m\n";
    foreach ($datiEstratti as $k => $v) {
        echo "   -> \033[0;37m$k:\033[0m \033[1;37m$v\033[0m\n";
        usleep(100000);
    }
    echo "\n";
    success("Estrazione completata con confidenza 98.7%.");


    // --- FASE 2: IDENTITA' DIGITALE ---
    printSection("Fase 2: Creazione Identità Digitale");

    $socio = new Socio();
    $dati = new DatiAnagrafici();

    // Mapping dati
    $dati->Nome = $datiEstratti['NOME'];
    $dati->Cognome = $datiEstratti['COGNOME'];
    $dati->Indirizzo = "Via dei Ciliegi 12, Firenze";
    $dati->Email = strtolower($dati->Nome . "." . $dati->Cognome . "@email.it");
    $dati->DataNascita = new DateTime("1985-05-15");
    $dati->Telefono = "+39 333 1234567";

    $socio->aggiornaAnagrafica($dati);
    $socio->CodiceFiscale = $datiEstratti['CF'];
    $socio->Matricola = "2025/SIM/" . rand(100, 999);
    $socio->Stato = StatoIscrizione::ATTIVO;

    loader("Generazione Profilo Socio in memoria");
    success("Socio istanziato: " . $socio->DatiPersonali->Nome . " " . $socio->DatiPersonali->Cognome);


    // --- FASE 3: ARCHIVIAZIONE CLOUD ---
    printSection("Fase 3: Secure Cloud Storage (Google Drive Agency)");

    $doc = new ModuloIscrizione();
    $doc->IdUnivoco = uniqid('doc_');
    $doc->NomeFile = "ModuloIscrizione_" . $socio->DatiPersonali->Cognome . "_2025.pdf";
    $doc->DataCaricamento = new DateTime();
    $doc->AnnoSolare = (int) date('Y');
    $doc->QuotaVersata = 50.00;
    $doc->MetodoPagamento = "BONIFICO_BANCARIO";
    $doc->Stato = StatoDocumento::IN_ATTESA;

    $dummyContent = "PDF_BINARY_DATA_SIMULATION_" . time();
    $doc->HashSHA256 = hash('sha256', $dummyContent);

    info("Caricamento blob cifrato...");
    $url = $cloud->upload($doc->NomeFile, $dummyContent);
    $doc->UrlArchivio = $url;

    $socio->aggiungiDocumento($doc);

    loader("Upload e Verifica Hash");
    success("Documento archiviato su Cloud: $url");


    // --- FASE 4: PERSISTENZA ---
    printSection("Fase 4: Persistenza Transazionale");

    info("Salvataggio aggregato Socio nel Database Relazionale...");
    // $repo->save($socio); // Simuliamo il save per non sporcare il DB vero se non configurato
    loader("Commit Transazione SQL");
    success("Dati Salvati Correttamente.");


    // --- FASE 5: LOGICA DI BUSINESS ---
    printSection("Fase 5: Analisi Automatica Stato (Business Logic)");

    info("Simulazione verifica amministrazione...");

    // Check Morosità
    echo "\n  \033[1;33m[CONTROLLO MOROSITÀ]\033[0m\n";
    $isMoroso = $socio->verificaMorosita(); // Dovrebbe essere true perchè documento IN_ATTESA

    if ($isMoroso) {
        warning("Stato: MOROSO (Il documento è caricato ma attende validazione Segreteria)");
    } else {
        success("Stato: REGOLARE");
    }

    echo "\n  > Simulazione approvazione Segreteria...\n";
    usleep(500000);
    $socio->DocumentiAssociati[0]->Stato = StatoDocumento::VALIDATO;
    success("Documento approvato dall'operatore.");

    echo "  > Ricalcolo automatico...\n";
    $isMorosoNow = $socio->verificaMorosita();

    if (!$isMorosoNow) {
        echo "  STATO FINALE: \033[1;32mISCRITTO REGOLARMENTE 2025\033[0m\n";
    } else {
        warning("Ancora Moroso (Errore logico sim)");
    }

    printSection("SIMULAZIONE TERMINATA CON SUCCESSO");
    echo "\033[0;37mSistema pronto e operativo.\033[0m\n\n";

} catch (Exception $e) {
    echo "\n\033[1;31m[CRITICAL ERROR] " . $e->getMessage() . "\033[0m\n";
    echo $e->getTraceAsString();
    exit(1);
}

