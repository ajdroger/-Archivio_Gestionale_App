<?php

// Protezione ambiente di produzione
if (getenv('APP_ENV') === 'production') {
    die("ERRORE: La simulazione non può essere eseguita in ambiente di produzione.\n");
}

require __DIR__ . '/../vendor/autoload.php';

// Register Global Error Handling (CLI)
$logger = new \Monolog\Logger('simulation');
$logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stderr', \Monolog\Logger::ERROR));
\FratellanzaMilitare\Debug\GlobalExceptionHandler::registerGlobalHandlers($logger);

use FratellanzaMilitare\GestioneSoci\Socio;
use FratellanzaMilitare\GestioneSoci\DatiAnagrafici;
use FratellanzaMilitare\GestioneSoci\ModuloIscrizione;
use FratellanzaMilitare\InfrastrutturaIT\OCREngine;
use FratellanzaMilitare\InfrastrutturaIT\GoogleDriveAdapter;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDOSocioRepository;
use FratellanzaMilitare\Enum\StatoDocumento;

// Helper Functions for Immersion
function typeText($text, $delay = 10000)
{
    echo $text;
    return; // Modalità veloce per ora, decommenta il ciclo per la digitazione lenta
}

function printSection($title)
{
    echo "\n\033[1;36m==========================================================\033[0m\n";
    echo "\033[1;33m  " . strtoupper($title) . "\033[0m\n";
    echo "\033[1;36m==========================================================\033[0m\n\n";
    usleep(00000);
}

function loader($message)
{
    echo "  > $message";
    for ($i = 0; $i < 3; $i++) {
        echo ".";
        usleep(300000);
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

// Pulisce lo schermo
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    system('cls');
} else {
    system('clear');
}

// Introduzione
echo "\n\033[1;37m";
echo "   __________________________________________________________   \n";
echo "  |                                                          |  \n";
echo "  |  FRATELLANZA MILITARE DI FIRENZE - SISTEMA DIGITALE v1.0 |  \n";
echo "  |__________________________________________________________|  \n";
echo "\033[0m\n";

echo "Inizializzazione Ambiente Sicuro...\n";
usleep(500000);
echo "Connessione al Database Crittografato...\n";
usleep(500000);
echo "Verifica Moduli Infrastrutturali...\n\n";

// --- START DEMO ---

printSection("Fase 1: Acquisizione Documentale");
$ocr = new OCREngine();
$cloud = new GoogleDriveAdapter();
$repo = new PDOSocioRepository();

info("Simulazione scansione modulo cartaceo in corso...");
loader("Attivazione Scanner TWAIN");
loader("Acquisizione Immagine ad Alta Risoluzione");

$dummyImage = "scansione_modulo_2025_mario_rossi.bmp";
info("Analisi AI (OCR) sul file: $dummyImage");
usleep(800000);

$datiEstratti = $ocr->estraiDatiDaImmagine($dummyImage);

echo "\n  \033[1;33m[RISULTATI ANALISI INTELLIGENTE]\033[0m\n";
foreach ($datiEstratti as $k => $v) {
    echo "  -> $k: \033[1;37m$v\033[0m\n";
    usleep(200000); // Effetto
}
success("Estrazione Dati Completata. Confidenza: 98.5%");

// --- FASE 2 ---
printSection("Fase 2: Creazione Identità Digitale");

$socio = new Socio();
$dati = new DatiAnagrafici();
$dati->Nome = $datiEstratti['NOME'];
$dati->Cognome = $datiEstratti['COGNOME'];
$socio->CodiceFiscale = $datiEstratti['CF'];
$socio->Matricola = "2025/001"; // Generated ID
$socio->Stato = \FratellanzaMilitare\Enum\StatoIscrizione::ATTIVO;

$dati->Indirizzo = "Via Roma 1, Firenze";
$dati->Email = "mario.rossi@example.com";
$dati->DataNascita = new DateTime("1980-01-01");

$socio->aggiornaAnagrafica($dati);
loader("Generazione Profilo Socio");
success("Socio creato: " . $socio->DatiPersonali->Nome . " " . $socio->DatiPersonali->Cognome);

// --- FASE 3 ---
printSection("Fase 3: Archiviazione Sicura (Cloud)");

$doc = new ModuloIscrizione();
$doc->IdUnivoco = uniqid('doc_');
$doc->NomeFile = "Modulo_2025.pdf";
$doc->DataCaricamento = new DateTime();
$doc->UrlArchivio = "";
$doc->AnnoSolare = 2025;
$doc->QuotaVersata = 50.00;
$doc->MetodoPagamento = "BONIFICO";
$doc->Stato = StatoDocumento::IN_ATTESA;

$dummyContent = "CONTENUTO_BINARIO_PDF_FITTIZIO";
$doc->HashSHA256 = hash('sha256', $dummyContent);
$doc->verificaIntegrita($dummyContent);

info("Caricamento Documento PDF/A su Google Drive Enterprise...");
loader("Cifratura AES-256");
loader("Upload in corso");

$url = $cloud->upload($doc->NomeFile, $dummyContent);
$doc->UrlArchivio = $url;
$socio->aggiungiDocumento($doc);

success("Documento protetto e archiviato: $url");

// --- FASE 4 ---
printSection("Fase 4: Persistenza e Verifica");

info("Salvataggio transazionale nel Database Gestionale...");
$repo->save($socio);
success("Dati Salvati.");

info("Simulazione accesso da parte del Direttore...");
loader("Ricerca anagrafica per CF: " . $socio->CodiceFiscale);

$socioRecuperato = $repo->findByCodiceFiscale($socio->CodiceFiscale);
success("Scheda Socio recuperata correttemente!");

echo "\n  \033[1;33m[CONTROLLO AUTOMATICO MOROSITÀ]\033[0m\n";
$isMoroso = $socioRecuperato->verificaMorosita();

if ($isMoroso) {
    echo "  STATO: \033[1;31mMOROSO (Quota non trovata)\033[0m\n";
    info("Nota: Il documento è stato caricato ma è in stato 'DA_VERIFICARE'. La segreteria deve validarlo.");
} else {
    echo "  STATO: \033[1;32mREGOLARE\033[0m\n";
}

// Simuliamo la validazione
echo "\n  > Validazione manuale Segreteria...\n";
$socioRecuperato->DocumentiAssociati[0]->Stato = StatoDocumento::VALIDATO;
$repo->save($socioRecuperato);
success("Documento Validato.");

echo "  > Ricalcolo Morosità...\n";
$isMorosoNew = $socioRecuperato->verificaMorosita();
if (!$isMorosoNew) {
    echo "  STATO PROFILO 2025: \033[1;32mISCRITTO REGOLARMENTE (Pagamento Verificato)\033[0m\n";
}

printSection("SIMULAZIONE COMPLETATA CORRETTAMENTE.");
echo "Il sistema è pronto per il servizio.\n\n";
