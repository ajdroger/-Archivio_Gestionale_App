<?php

use Faker\Factory;
use DI\ContainerBuilder;
use MCAG\Service\RegistrationService;
use MCAG\Service\FiscalCodeCalculator;

require __DIR__ . '/../../vendor/autoload.php';

// 1. Load Env Vars (Critical for Database Connection)
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();
} catch (\Exception $e) {
    // Maybe env is already loaded or file missing, proceed but warn
    echo "Warning: .env loading failed or file missing.\n";
}

// Boot Container manuale
$containerBuilder = new ContainerBuilder();
$definitions = require __DIR__ . '/../../config/container.php';
foreach ($definitions as $def) {
    if (file_exists($def)) {
        $containerBuilder->addDefinitions($def);
    }
}
$container = $containerBuilder->build();

// Setup Audit Trail (Singleton Bridge)
$auditTrail = \MCAG\SecurityLayer\AuditTrail::getInstance();
$auditTrail->setLogger($container->get('audit_logger'));
$auditTrail->setPdo($container->get(PDO::class));

$service = $container->get(RegistrationService::class);

// FiscalCodeCalculator resolve
try {
    $cfCalc = $container->get(FiscalCodeCalculator::class);
} catch (\Exception $e) {
    $cfCalc = new FiscalCodeCalculator();
}

$faker = Factory::create('it_IT');

echo "Inizio Massive Seeding (1000 Soci)...\n";
echo "Nota: Generazione Documenti PDF attiva.\n";

$totals = ['MILITARE' => 0, 'CIVILE' => 0, 'ERRORI' => 0];

for ($i = 0; $i < 1000; $i++) {
    try {
        // Logica random weighted: 40% Militari
        $isMilitary = $faker->boolean(40);

        $sesso = $faker->randomElement(['M', 'F']);
        $nome = $sesso == 'M' ? $faker->firstNameMale : $faker->firstNameFemale;
        $cognome = $faker->lastName;
        $dataNascitaObj = $faker->dateTimeBetween('-60 years', '-20 years');
        $dataNascita = $dataNascitaObj->format('Y-m-d');
        $luogo = $faker->city;

        // Pulizia stringhe caratteri speciali
        $nomeClean = preg_replace('/[^A-Za-z ]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $nome));
        $cognomeClean = preg_replace('/[^A-Za-z ]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $cognome));
        $luogoClean = preg_replace('/[^A-Za-z ]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $luogo));

        try {
            $cf = $cfCalc->calculate($nome, $cognome, $dataNascita, $sesso, $luogo);
        } catch (\Exception $e) {
            $luogo = 'ROMA'; // Fallback
            $cf = $cfCalc->calculate($nome, $cognome, $dataNascita, $sesso, $luogo);
        }

        $payload = [
            'codice_fiscale' => $cf,
            'nome' => strtoupper($nome),
            'cognome' => strtoupper($cognome),
            'data_nascita' => $dataNascita,
            'sesso' => $sesso,
            'luogo_nascita' => strtoupper($luogo),
            'stato_civile' => strtoupper($faker->randomElement(['CELIBE/NUBILE', 'CONIUGATO/A', 'DIVORZIATO/A'])),
            'indirizzo' => strtoupper($faker->address),
            'email' => $faker->email,
            'telefono' => $faker->phoneNumber,
            'titolo_studio' => strtoupper($faker->randomElement(['DIPLOMA', 'LAUREA', 'LICENZA MEDIA', 'DOTTORATO'])),
            'professione' => strtoupper($faker->jobTitle),
            'pagamento_effettuato' => '1',

            // Dati Militari / Sanitari (randomizzati)
            'gruppo_sanguigno' => $faker->randomElement(['0 Rh+', 'A Rh+', 'B Rh-', 'AB Rh+', null]),
            'note_mediche' => $faker->boolean(20) ? 'ALLERGIA AL ' . strtoupper($faker->word) : null,
            'contatto_emergenza' => strtoupper($faker->firstName) . ' ' . $faker->phoneNumber,
        ];

        if ($isMilitary) {
            $payload['tipo_profilo'] = 'MILITARE';
            $payload['grado'] = strtoupper($faker->randomElement(['CAPORALE', 'SERGENTE', 'MARESCIALLO', 'CAPITANO', 'TENENTE', 'COLONNELLO']));
            $payload['corpo_appartenenza'] = strtoupper($faker->randomElement(['ALPINI', 'BERSAGLIERI', 'FANTERIA', 'CARABINIERI', 'AERONAUTICA', 'MARINA']));
            $payload['data_arruolamento'] = $faker->dateTimeBetween('-25 years', '-10 years')->format('Y-m-d');
            $payload['data_congedo'] = $faker->boolean(70) ? $faker->dateTimeBetween('-10 years', 'now')->format('Y-m-d') : null;
            $totals['MILITARE']++;
        } else {
            $payload['tipo_profilo'] = 'CIVILE';
            $totals['CIVILE']++;
        }

        $service->registerNewMember($payload);

        if (($i + 1) % 50 == 0) {
            echo "Processati " . ($i + 1) . " soci... (Mem: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB)\n";
        }

    } catch (\Exception $e) {
        $totals['ERRORI']++;
        // echo "Errore iterazione $i: " . $e->getMessage() . "\n";
    }
}

echo "\nSEEDING COMPLETATO.\n";
print_r($totals);

