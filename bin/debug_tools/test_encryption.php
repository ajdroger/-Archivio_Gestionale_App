<?php
require __DIR__ . '/../../vendor/autoload.php';

use MCAG\SecurityLayer\TotpEncryptionService;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

try {
    $service = TotpEncryptionService::getInstance();
    echo "✅ Service instantiated successfully.\n";

    $test = "Hello World";
    $enc = $service->encrypt($test);
    $dec = $service->decrypt($enc);

    if ($dec === $test) {
        echo "✅ Use Test (Encrypt/Decrypt) Passed.\n";
    } else {
        echo "❌ Decryption mismatch.\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

