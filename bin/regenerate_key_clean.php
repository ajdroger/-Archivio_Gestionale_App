<?php
require __DIR__ . '/../vendor/autoload.php';

use Defuse\Crypto\Key;

try {
    // 1. Generate NEW Key
    $keyObject = Key::createNewRandomKey();
    $newKeyString = $keyObject->saveToAsciiSafeString();

    echo "Generated New Key: $newLines\n(Length: " . strlen($newKeyString) . ")\n";

    // 2. Update .env
    $envFile = __DIR__ . '/../.env';
    $content = file_get_contents($envFile);
    $lines = explode("\n", $content);
    $outputLines = [];

    foreach ($lines as $line) {
        $trim = trim($line);
        if (empty($trim))
            continue;
        if (str_starts_with($trim, '#')) {
            $outputLines[] = $trim;
            continue;
        }
        if (str_contains($trim, '=')) {
            [$k, $v] = explode('=', $trim, 2);
            if (trim($k) === 'TOTP_ENCRYPTION_KEY')
                continue;
            $outputLines[] = $trim;
        }
    }

    $outputLines[] = "TOTP_ENCRYPTION_KEY=$newKeyString";
    file_put_contents($envFile, implode("\n", $outputLines) . "\n");
    echo "Updated .env with new key.\n";

    // 3. Verify
    $loadedKey = Key::loadFromAsciiSafeString($newKeyString);
    echo "✅ Verification: Key loaded successfully back from string.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
