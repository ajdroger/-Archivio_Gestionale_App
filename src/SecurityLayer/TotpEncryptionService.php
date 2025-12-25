<?php

namespace FratellanzaMilitare\SecurityLayer;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;

/**
 * TOTP Secrets Encryption Service
 * 
 * Provides encryption/decryption for TOTP secrets stored in database.
 * Uses defuse/php-encryption for authenticated symmetric encryption.
 */
class TotpEncryptionService
{
    private Key $key;
    private static ?TotpEncryptionService $instance = null;

    private function __construct()
    {
        $keyString = $_ENV['TOTP_ENCRYPTION_KEY'] ?? null;

        if ($keyString === null || $keyString === '') {
            // Generate a new key if not set (for initial setup)
            // In production, this should be set in .env
            throw new \RuntimeException(
                'TOTP_ENCRYPTION_KEY non configurata. Eseguire: php bin/setup/generate_totp_key.php'
            );
        }

        $this->key = Key::loadFromAsciiSafeString($keyString);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Encrypt a TOTP secret for storage
     * 
     * @param string $plainSecret Base32 TOTP secret
     * @return string Encrypted and base64-encoded ciphertext
     */
    public function encrypt(string $plainSecret): string
    {
        $ciphertext = Crypto::encrypt($plainSecret, $this->key);
        return base64_encode($ciphertext);
    }

    /**
     * Decrypt a stored TOTP secret
     * 
     * @param string $encryptedSecret Base64-encoded ciphertext
     * @return string|null Decrypted Base32 secret, or null on failure
     */
    public function decrypt(string $encryptedSecret): ?string
    {
        try {
            // Check if it's actually encrypted (starts with base64 of Defuse format)
            if (!$this->isEncrypted($encryptedSecret)) {
                // Return as-is if not encrypted (legacy secrets)
                return $encryptedSecret;
            }

            $ciphertext = base64_decode($encryptedSecret, true);
            if ($ciphertext === false) {
                return $encryptedSecret; // Return original if not valid base64
            }

            return Crypto::decrypt($ciphertext, $this->key);
        } catch (WrongKeyOrModifiedCiphertextException $e) {
            // Decryption failed - possibly a legacy unencrypted secret
            return $encryptedSecret;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if a secret appears to be encrypted
     * Defuse encrypted strings are quite long (>100 chars after base64)
     */
    public function isEncrypted(string $secret): bool
    {
        // Base32 TOTP secrets are typically 16-32 chars
        // Encrypted secrets (base64 of Defuse output) are much longer
        return strlen($secret) > 100;
    }

    /**
     * Generate a new encryption key (for setup)
     * 
     * @return string ASCII-safe key string for .env
     */
    public static function generateKey(): string
    {
        $key = Key::createNewRandomKey();
        return $key->saveToAsciiSafeString();
    }
}
