<?php

namespace MCAG\Security\Encryption;

use Exception;

/**
 * Servizio per la gestione della crittografia a livello di colonna.
 * Utilizzato per proteggere dati sensibili (PII) nel database.
 * 
 * TODO: Integrare 'defuse/php-encryption' per gestione chiavi più robusta.
 */
class ColumnEncryptor
{
    private string $key;
    private string $cipher = 'aes-256-gcm';

    public function __construct(string $key = '')
    {
        // Se la chiave non è fornita, usa una chiave di fallback (SOLO DEV) o lancia eccezione in PROD.
        // In produzione, la chiave deve essere caricata da Variabile d'Ambiente sicura.
        $this->key = $key ?: ($_ENV['DB_ENCRYPTION_KEY'] ?? 'fallback-dev-key-32-bytes-long-!!');

        if (strlen($this->key) !== 32) {
            // Hash to ensure 32 bytes if not exact
            $this->key = hash('sha256', $this->key, true);
        }
    }

    public function encrypt(string $plaintext): string
    {
        if (empty($plaintext))
            return '';

        $ivLen = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivLen);
        $tag = ""; // Reference for GCM tag

        $ciphertext = openssl_encrypt(
            $plaintext,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($ciphertext === false) {
            throw new Exception("Encryption failed");
        }

        // Return base64 encoded string containing IV + Tag + Ciphertext
        return base64_encode($iv . $tag . $ciphertext);
    }

    public function decrypt(string $encrypted): string
    {
        if (empty($encrypted))
            return '';

        $data = base64_decode($encrypted);
        $ivLen = openssl_cipher_iv_length($this->cipher);
        $tagLen = 16; // GCM tag length

        if (strlen($data) < $ivLen + $tagLen) {
            return ''; // Invalid data
        }

        $iv = substr($data, 0, $ivLen);
        $tag = substr($data, $ivLen, $tagLen);
        $ciphertext = substr($data, $ivLen + $tagLen);

        $plaintext = openssl_decrypt(
            $ciphertext,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            // Decryption failed (wrong key or tampered data)
            // Log error securely
            return '[ENCRYPTED]';
        }

        return $plaintext;
    }
}


