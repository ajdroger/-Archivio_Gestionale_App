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

// Load Env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

// Set high execution time
set_time_limit(600);
ini_set('memory_limit', '512M');

echo "\n============================================\n";
echo "   MASSIVE SEEDER v3.1 - HIGH FIDELITY      \n";
echo "============================================\n";

try {
    $pdo = DatabaseConnection::getConnection();

    // 1. Dependencies
    $repo = new PDOSocioRepository($pdo);
    $validator = new ValidationService();
    $pdfService = new PdfGenerationService();
    $emailService = new FileEmailService(__DIR__ . '/../../logs/debug/massive_seeder_emails.txt');

    $logger = new Logger('seeder');
    $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/debug/seeder.log', Logger::DEBUG));

    $registrationService = new RegistrationService(
        $repo,
        $validator,
        $pdfService,
        $emailService,
        $logger
    );

    // 2. Database Cleanup
    echo "[!] Svuotamento tabelle 'soci' e 'documenti'...\n";
    $pdo->exec("DELETE FROM documenti");
    $pdo->exec("DELETE FROM soci");
    echo "[OK] Cleanup completato.\n";

    // 3. Realistic Data Pools
    $nomi = [
        'MARIO',
        'LUIGI',
        'GIOVANNI',
        'FRANCESCO',
        'PAOLO',
        'ROBERTO',
        'ANTONIO',
        'MICHELE',
        'GIUSEPPE',
        'RICCARDO',
        'ALBERTO',
        'STEFANO',
        'MARCO',
        'ALESSANDRO',
        'ANDREA',
        'LORENZO',
        'MATTEO',
        'SIMONE',
        'DAVIDE',
        'CRISTIAN',
        'ANNA',
        'MARIA',
        'GIULIA',
        'ELENA',
        'LAURA',
        'FRANCESCA',
        'SARA',
        'CHIARA',
        'SILVIA',
        'ELISA',
        'VALENTINA',
        'ALESSIA',
        'MARTINA',
        'FEDERICA',
        'BEATRICE',
        'ALICE',
        'GIORGIA',
        'EMMA',
        'GRETA',
        'IRENE'
    ];

    $cognomi = [
        'ROSSI',
        'BIANCHI',
        'ESPOSITO',
        'RICCI',
        'ROMANO',
        'COLOMBO',
        'FERRARI',
        'MARINO',
        'GRECO',
        'BRUNO',
        'GALLO',
        'CONTI',
        'DE LUCA',
        'MANCINI',
        'COSTA',
        'GIORDANO',
        'RIZZO',
        'LOMBARDI',
        'MORETTI',
        'BARBIERI',
        'FONTANA',
        'SANTORO',
        'MARIANI',
        'RINALDI',
        'CARUSO',
        'FERRARA',
        'GALLI',
        'MARTINI',
        'LEONE',
        'LONGO',
        'GENTILE',
        'MARTINELI',
        'VITALE',
        'SERRA',
        'COPPOLA',
        'DE ANGELIS',
        'PARISI',
        'MESSINA',
        'VILLA',
        'FABBRI'
    ];

    $vie = [
        'VIA ROMA',
        'VIA GARIBALDI',
        'CORSO ITALIA',
        'VIA DANTE',
        'VIA MAZZINI',
        'VIA VERDI',
        'PIAZZA DUOMO',
        'VIALE KENNEDY',
        'VIA DEI MILLE',
        'CORSO VITTORIO EMANUELE',
        'VIA BOLOGNA',
        'VIA FIRENZE',
        'VIA MILANO'
    ];

    $citta = ['FIRENZE', 'PRATO', 'PISTOIA', 'LUCCA', 'PISA', 'AREZZO', 'SIENA', 'GROSSETO', 'LIVORNO', 'MASSA'];

    // 4. Generation Loop (300 Users)
    $targetCount = 300;
    echo "[+] Inizio generazione di $targetCount soci meticolosi...\n";

    $successCount = 0;
    for ($i = 0; $i < $targetCount; $i++) {
        $nome = $nomi[array_rand($nomi)];
        $cognome = $cognomi[array_rand($cognomi)];

        // Generate CF using regex-compliant characters
        // Last char is CIN [A-Z], Belfiore [A-Z][0-9]{3}
        // Let's build it carefully
        $months = ['A', 'B', 'C', 'D', 'E', 'H', 'L', 'M', 'P', 'R', 'S', 'T'];
        $cf = strtoupper(substr($cognome, 0, 3));
        while (strlen($cf) < 3)
            $cf .= 'X';
        $cfNome = strtoupper(substr($nome, 0, 3));
        while (strlen($cfNome) < 3)
            $cfNome .= 'X';
        $cf .= $cfNome;

        $cf .= rand(50, 99); // Anno
        $cf .= $months[array_rand($months)]; // Mese
        $cf .= str_pad(rand(1, 31), 2, '0', STR_PAD_LEFT); // Giorno
        $cf .= 'H501'; // Firenze
        $cf .= chr(rand(65, 90)); // CIN

        // Data registration
        $data = [
            'nome' => $nome,
            'cognome' => $cognome,
            'codice_fiscale' => $cf,
            'data_nascita' => (1950 + rand(0, 50)) . '-' . str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT),
            'indirizzo' => $vie[array_rand($vie)] . ' ' . rand(1, 200) . ', ' . $citta[array_rand($citta)],
            'email' => strtolower($nome . '.' . $cognome . '.' . $i . '@email.test'),
            'telefono' => '+39 3' . rand(0, 9) . rand(0, 9) . ' ' . rand(1000000, 9999999),
            'pagamento_effettuato' => '1', // Ensure PDF generation
            'matricola' => date('Y') . '/SEED/' . str_pad($i + 1, 4, '0', STR_PAD_LEFT)
        ];

        try {
            $registrationService->registerNewMember($data);
            $successCount++;
            if ($successCount % 10 === 0) {
                echo "[$successCount/$targetCount] ";
                if ($successCount % 50 === 0)
                    echo "\n";
            }
        } catch (\Exception $e) {
            // Collision or validation error, skip and adjust count
            $logger->error("Errore generazione socio $i: " . $e->getMessage());
            $i--; // Retry to reach 300
        }
    }

    echo "\n\n============================================\n";
    echo "[SUCCESS] Generati $successCount nuovi soci con:\n";
    echo "  - Anagrafica completa e realistica\n";
    echo "  - Documentazione PDF (Ricevuta 2025)\n";
    echo "  - Log delle email inviate\n";
    echo "  - Stato ATTIVO e VALIDATO\n";
    echo "============================================\n";

} catch (\Exception $e) {
    echo "\n[CRITICAL ERROR] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
