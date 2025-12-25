<?php

require __DIR__ . '/../../vendor/autoload.php';

// Register Global Error Handling
// Register Global Error Handling
$logger = new \Monolog\Logger('test_2fa');
$logger->pushHandler(new \Monolog\Handler\StreamHandler(__DIR__ . '/../../logs/test.log', \Monolog\Logger::DEBUG));
\FratellanzaMilitare\Debug\GlobalExceptionHandler::registerGlobalHandlers($logger);

use FratellanzaMilitare\SecurityLayer\Amministratore;

echo "=== TEST VERIFICA 2FA/TOTP ===\n\n";

// Crea un utente amministratore con 2FA attivo
$admin = new Amministratore();
$admin->ID = 1;
$admin->Username = "admin_test";

// Imposta una password usando il metodo pubblico
$passwordChiara = "SecurePassword123!";
$admin->impostaPassword($passwordChiara);

// Imposta un secret 2FA (in produzione sarebbe generato e salvato durante il setup)
// Questo è un secret di esempio - in produzione sarebbe base32 decoded
$admin->Token2FA = "TESTSECRET2FAKEY1234567890";

echo "✓ Utente creato: {$admin->Username}\n";
echo "✓ Password impostata\n";
echo "✓ Token 2FA configurato\n\n";

// TEST 1: Login senza 2FA (deve fallire se 2FA è attivo)
echo "TEST 1: Login senza codice 2FA...\n";
$result1 = $admin->autentica($passwordChiara);
if (!$result1) {
    echo "  ✓ Login rifiutato correttamente (2FA richiesto)\n\n";
} else {
    echo "  ✗ ERRORE: Login accettato senza 2FA!\n\n";
}

// TEST 2: Login con codice 2FA non valido
echo "TEST 2: Login con codice 2FA non valido...\n";
$result2 = $admin->autentica($passwordChiara, "000000");
if (!$result2) {
    echo "  ✓ Login rifiutato correttamente (codice non valido)\n\n";
} else {
    echo "  ✗ ERRORE: Login accettato con codice errato!\n\n";
}

// TEST 3: Login con codice formato errato
echo "TEST 3: Login con formato codice errato...\n";
$result3 = $admin->autentica($passwordChiara, "ABC123");
if (!$result3) {
    echo "  ✓ Login rifiutato correttamente (formato invalido)\n\n";
} else {
    echo "  ✗ ERRORE: Login accettato con formato errato!\n\n";
}

// TEST 4: Utente senza 2FA configurato
echo "TEST 4: Utente senza 2FA configurato...\n";
$userSenza2FA = new Amministratore();
$userSenza2FA->ID = 2;
$userSenza2FA->Username = "user_no2fa";
$userSenza2FA->impostaPassword($passwordChiara);
$userSenza2FA->Token2FA = ""; // Nessun 2FA

$result4 = $userSenza2FA->autentica($passwordChiara);
if ($result4) {
    echo "  ✓ Login accettato (2FA non richiesto)\n\n";
} else {
    echo "  ✗ ERRORE: Login rifiutato senza motivo!\n\n";
}

echo "=== TEST COMPLETATI ===\n";
echo "\nNota: In produzione, il codice TOTP verrebbe generato da un'app\n";
echo "come Google Authenticator o Authy usando il secret condiviso.\n";
echo "Per testare con un codice reale, è necessario usare lo stesso\n";
echo "algoritmo TOTP (RFC 6238) sincronizzato con il server.\n";
