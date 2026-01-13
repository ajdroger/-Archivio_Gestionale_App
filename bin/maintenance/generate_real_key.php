<?php
require __DIR__ . '/../../vendor/autoload.php';

use Defuse\Crypto\Key;

try {
    $key = Key::createNewRandomKey();
    $keyString = $key->saveToAsciiSafeString();

    echo "Generated Valid Key: " . $keyString . "\n";

    $envFile = __DIR__ . '/../../.env';
    $content = file_get_contents($envFile);

    // Remove old key lines
    $lines = explode("\n", $content);
    $newLines = [];
    foreach ($lines as $line) {
        if (!str_contains($line, 'TOTP_ENCRYPTION_KEY')) {
            $newLines[] = trim($line);
        }
    }

    // Add new key
    $newLines[] = "TOTP_ENCRYPTION_KEY=" . $keyString;

    file_put_contents($envFile, implode("\n", $newLines) . "\n");
    echo "Successfully updated .env with new valid key.\n";

} catch (\Exception $e) {
    echo "Error generating key: " . $e->getMessage() . "\n";
    exit(1);
}

