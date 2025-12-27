<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection;
use FratellanzaMilitare\InfrastrutturaIT\Persistence\PDOSocioRepository;
use FratellanzaMilitare\Service\RegistrationService;
use FratellanzaMilitare\Service\ValidationService;
use FratellanzaMilitare\Service\PdfGenerationService;
use FratellanzaMilitare\Service\FileEmailService;
use FratellanzaMilitare\Enum\StatoIscrizione;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Load Env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

set_time_limit(600);
ini_set('memory_limit', '512M');

echo "\n============================================\n";
echo "   MASSIVE SEEDER v3.2 - MIXED STATUS       \n";
echo "============================================\n";

try {
    $pdo = DatabaseConnection::getConnection();
    $repo = new PDOSocioRepository($pdo);
    $validator = new ValidationService();
    $pdfService = new PdfGenerationService();
    $emailService = new FileEmailService(__DIR__ . '/../../logs/massive_seeder_emails.txt');

    $logger = new Logger('seeder_mixed');
    $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/seeder.log', Logger::DEBUG));

    $registrationService = new RegistrationService($repo, $validator, $pdfService, $emailService, $logger);

    // Realistic Pools
    $nomi = ['ALESSANDRO', 'MATTEO', 'LORENZO', 'MATTIA', 'FRANCESCO', 'RICCARDO', 'DAVIDE', 'LEONARDO', 'GABRIELE', 'LUCA', 'GIULIA', 'SARA', 'CHIARA', 'MARTINA', 'ALICE', 'ALESSIA', 'GIORGIA', 'AURORA', 'EMMA', 'SOFIA'];
    $cognomi = ['ROSSI', 'BIANCHI', 'ESPOSITO', 'RICCI', 'ROMANO', 'COLOMBO', 'FERRARI', 'MARINO', 'GRECO', 'BRUNO', 'GALLO', 'CONTI', 'DE LUCA', 'MANCINI', 'COSTA', 'GIORDANO', 'RIZZO', 'LOMBARDI', 'MORETTI', 'BARBIERI'];
    $vie = ['VIA ROMA', 'VIA MAZZINI', 'CORSO ITALIA', 'VIA VERDI', 'VIA GARIBALDI', 'PIAZZA DUOMO'];
    $citta = ['FIRENZE', 'PRATO', 'SCANDICCI', 'EMPOLI', 'SESTO FIORENTINO'];

    $targetCount = 200;
    echo "[+] Aggiunta di $targetCount soci con stati diversificati...\n";

    $successCount = 0;
    for ($i = 0; $i < $targetCount; $i++) {
        $nome = $nomi[array_rand($nomi)];
        $cognome = $cognomi[array_rand($cognomi)];

        // CF Generator
        $months = ['A', 'B', 'C', 'D', 'E', 'H', 'L', 'M', 'P', 'R', 'S', 'T'];
        $cf = strtoupper(substr($cognome, 0, 3) . substr($nome, 0, 3)) . rand(50, 99) . $months[array_rand($months)] . str_pad(rand(1, 31), 2, '0', STR_PAD_LEFT) . 'H501' . chr(rand(65, 90));

        // Distribution logic
        // 0-66: Moroso (ATTIVO but no payment)
        // 67-133: Sospeso (SOSPESO + payment)
        // 134-200: Radiato (DECADUTO)

        $payment = '1';
        $targetStatus = StatoIscrizione::ATTIVO;
        $typeLabel = "";

        if ($i < 66) {
            $payment = '0'; // Moroso
            $typeLabel = "MOROSO";
        } elseif ($i < 133) {
            $targetStatus = StatoIscrizione::SOSPESO;
            $typeLabel = "SOSPESO";
        } else {
            $targetStatus = StatoIscrizione::DECADUTO;
            $typeLabel = "DECADUTO";
            $payment = '0'; // Usually no docs for dead/resigned
        }

        $data = [
            'nome' => $nome,
            'cognome' => $cognome,
            'codice_fiscale' => $cf,
            'data_nascita' => (1950 + rand(0, 50)) . '-' . str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT),
            'indirizzo' => $vie[array_rand($vie)] . ' ' . rand(1, 200) . ', ' . $citta[array_rand($citta)],
            'email' => strtolower($nome . '.' . $cognome . '.mix' . $i . '@email.test'),
            'telefono' => '+39 3' . rand(0, 9) . rand(0, 9) . ' ' . rand(1000000, 9999999),
            'pagamento_effettuato' => $payment,
            'matricola' => date('Y') . '/MIX/' . str_pad($i + 1, 4, '0', STR_PAD_LEFT)
        ];

        try {
            $socio = $registrationService->registerNewMember($data);

            // Manual override status if not ATTIVO
            if ($targetStatus !== StatoIscrizione::ATTIVO) {
                $socio->Stato = $targetStatus;
                $repo->save($socio);
            }

            $successCount++;
            if ($successCount % 10 === 0)
                echo ".";
        } catch (\Exception $e) {
            $i--; // Retry on collision
        }
    }

    echo "\n\n[SUCCESS] Aggiunti altri $successCount soci con stati misti.\n";
    echo "============================================\n";

} catch (\Exception $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
}
