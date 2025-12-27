<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDOSocioRepository;
use FratellanzaMilitare\Service\RegistrationService;
use FratellanzaMilitare\Service\ValidationService;
use FratellanzaMilitare\Service\PdfGenerationService;
use FratellanzaMilitare\Service\FileEmailService;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Script di Ripristino Soci (500)
 * 
 * Questo strumento "One-Click" svuota la tabella soci e la ripopola
 * con 500 profili realistici, completi di documenti e storico.
 * Utile per reset rapidi in ambiente di sviluppo/test o dopo cancellazioni accidentali.
 */

// Load Env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

// Set high execution time
set_time_limit(900);
ini_set('memory_limit', '512M');

echo "\n================================================\n";
echo "   RIPRISTINO SOCI (500 UNITÀ) - ONE CLICK      \n";
echo "================================================\n";

try {
    $pdo = DatabaseConnection::getConnection();

    // 1. Dependencies
    $repo = new PDOSocioRepository($pdo);
    $validator = new ValidationService();
    $pdfService = new PdfGenerationService();
    // Usa un file di log diverso per evitare lock
    $emailService = new FileEmailService(__DIR__ . '/../../logs/debug/restore_soci_emails.txt');

    $logger = new Logger('restore_soci');
    $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/debug/restore_soci.log', Logger::DEBUG));

    $registrationService = new RegistrationService(
        $repo,
        $validator,
        $pdfService,
        $emailService,
        $logger
    );

    // 2. Database Cleanup
    echo "[!] Svuotamento database (Tabelle 'soci' e 'documenti')...\n";
    // Disabilita check FK per troncamento rapido
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE documenti");
    $pdo->exec("TRUNCATE TABLE soci");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "[OK] Pulizia completata. Archivi vuoti.\n";

    // 3. Realistic Data Pools
    $nomi = ['MARIO', 'LUIGI', 'GIOVANNI', 'FRANCESCO', 'PAOLO', 'ROBERTO', 'ANTONIO', 'MICHELE', 'GIUSEPPE', 'RICCARDO', 'ALBERTO', 'STEFANO', 'MARCO', 'ALESSANDRO', 'ANDREA', 'LORENZO', 'MATTEO', 'SIMONE', 'DAVIDE', 'CRISTIAN', 'ANNA', 'MARIA', 'GIULIA', 'ELENA', 'LAURA', 'FRANCESCA', 'SARA', 'CHIARA', 'SILVIA', 'ELISA', 'VALENTINA', 'ALESSIA', 'MARTINA', 'FEDERICA', 'BEATRICE', 'ALICE', 'GIORGIA', 'EMMA', 'GRETA', 'IRENE'];
    $cognomi = ['ROSSI', 'BIANCHI', 'ESPOSITO', 'RICCI', 'ROMANO', 'COLOMBO', 'FERRARI', 'MARINO', 'GRECO', 'BRUNO', 'GALLO', 'CONTI', 'DE LUCA', 'MANCINI', 'COSTA', 'GIORDANO', 'RIZZO', 'LOMBARDI', 'MORETTI', 'BARBIERI', 'FONTANA', 'SANTORO', 'MARIANI', 'RINALDI', 'CARUSO', 'FERRARA', 'GALLI', 'MARTINI', 'LEONE', 'LONGO', 'GENTILE', 'MARTINELI', 'VITALE', 'SERRA', 'COPPOLA', 'DE ANGELIS', 'PARISI', 'MESSINA', 'VILLA', 'FABBRI'];
    $vie = ['VIA ROMA', 'VIA GARIBALDI', 'CORSO ITALIA', 'VIA DANTE', 'VIA MAZZINI', 'VIA VERDI', 'PIAZZA DUOMO', 'VIALE KENNEDY', 'VIA DEI MILLE', 'CORSO VITTORIO EMANUELE', 'VIA BOLOGNA', 'VIA FIRENZE', 'VIA MILANO'];
    $citta = ['FIRENZE', 'PRATO', 'PISTOIA', 'LUCCA', 'PISA', 'AREZZO', 'SIENA', 'GROSSETO', 'LIVORNO', 'MASSA'];

    // 4. Generation Loop (500 Users)
    $targetCount = 500;
    echo "[+] Avvio rigenerazione di $targetCount soci...\n";

    $successCount = 0;
    for ($i = 0; $i < $targetCount; $i++) {
        $nome = $nomi[array_rand($nomi)];
        $cognome = $cognomi[array_rand($cognomi)];

        // Generate CF (Semplificato ma valido come lunghezza)
        $months = ['A', 'B', 'C', 'D', 'E', 'H', 'L', 'M', 'P', 'R', 'S', 'T'];
        $cf = strtoupper(substr($cognome, 0, 3));
        while (strlen($cf) < 3)
            $cf .= 'X';
        $cfNome = strtoupper(substr($nome, 0, 3));
        while (strlen($cfNome) < 3)
            $cfNome .= 'X';
        $cf .= $cfNome;
        $cf .= rand(50, 99);
        $cf .= $months[array_rand($months)];
        $cf .= str_pad(rand(1, 31), 2, '0', STR_PAD_LEFT);
        $cf .= 'H501';
        $cf .= chr(rand(65, 90));

        // Data registration
        $data = [
            'nome' => $nome,
            'cognome' => $cognome,
            'codice_fiscale' => $cf,
            'data_nascita' => (1950 + rand(0, 50)) . '-' . str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT),
            'indirizzo' => $vie[array_rand($vie)] . ' ' . rand(1, 200) . ', ' . $citta[array_rand($citta)],
            'email' => strtolower($nome . '.' . $cognome . '.' . $i . '@email.test'),
            'telefono' => '+39 3' . rand(0, 9) . rand(0, 9) . ' ' . rand(1000000, 9999999),
            'pagamento_effettuato' => '1',
            'matricola' => date('Y') . '/RESTORE/' . str_pad($i + 1, 4, '0', STR_PAD_LEFT)
        ];

        try {
            $registrationService->registerNewMember($data);
            $successCount++;
            if ($successCount % 50 === 0) {
                echo "   > Ripristinati $successCount/$targetCount soci...\n";
            }
        } catch (\Exception $e) {
            // Se fallisce per duplicato CF generato random, riprova decrementando i
            $i--;
        }
    }

    echo "\n\n================================================\n";
    echo "[SUCCESSO] Operazione Completata!\n";
    echo "  - Soci Rigenerati: $successCount\n";
    echo "  - Documenti Creati: Sì\n";
    echo "  - Stato: ATTIVO\n";
    echo "================================================\n";

} catch (\Exception $e) {
    echo "\n[ERRORE CRITICO] " . $e->getMessage() . "\n";
    exit(1);
}
