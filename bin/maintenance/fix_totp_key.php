<?php
require __DIR__ . '/../../vendor/autoload.php';

use FratellanzaMilitare\SecurityLayer\TotpEncryptionService;

echo "Generating new TOTP Encryption Key...\n";

try {
    $newKey = TotpEncryptionService::generateKey();
    echo "New Key: " . $newKey . "\n";

    $envPath = __DIR__ . '/../../.env';
    if (!file_exists($envPath)) {
        die("Error: .env file not found at $envPath\n");
    }

    $content = file_get_contents($envPath);

    // Check if key exists
    if (strpos($content, 'TOTP_ENCRYPTION_KEY=') !== false) {
        $content = preg_replace(
            '/^TOTP_ENCRYPTION_KEY=.*$/m',
            'TOTP_ENCRYPTION_KEY=' . $newKey,
            $content
        );
    } else {
        $content .= "\nTOTP_ENCRYPTION_KEY=" . $newKey . "\n";
    }

    file_put_contents($envPath, $content);
    echo "Successfully updated .env file with new key.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
