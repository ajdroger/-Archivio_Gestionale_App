<?php
/**
 * Generate TOTP Encryption Key
 * 
 * Run this script to generate a secure encryption key for TOTP secrets.
 * Copy the output to your .env file as TOTP_ENCRYPTION_KEY.
 * 
 * Usage: php bin/setup/generate_totp_key.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Defuse\Crypto\Key;

echo "=== TOTP Encryption Key Generator ===\n\n";

$key = Key::createNewRandomKey();
$keyString = $key->saveToAsciiSafeString();

echo "Add this line to your .env file:\n\n";
echo "TOTP_ENCRYPTION_KEY=\"{$keyString}\"\n\n";
echo "Length: " . strlen($keyString) . " characters\n";
echo "\n⚠️  IMPORTANT: Keep this key secure! If lost, 2FA for all users will break.\n";
echo "📋 Backup this key in a secure location.\n\n";
