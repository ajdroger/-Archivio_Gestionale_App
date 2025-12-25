<?php

namespace FratellanzaMilitare\SecurityLayer;

use OTPHP\TOTP;

class TotpProvider
{
    /**
     * Generate a random secret key (Base32).
     */
    public function generateSecret(int $length = 16): string
    {
        // OTPHP provides a way to generate secrets, or we can keep it simple.
        // TOTP::create() generates a secret automatically if not provided.
        // But to keep API consistent, we return a string.
        $totp = TOTP::create();
        return $totp->getSecret();
    }

    /**
     * Calculate the current code for the secret.
     */
    public function getCode(string $secret, ?int $timeSlice = null): string
    {
        // Note: OTPHP handles time automatically.
        // If timeSlice is passed, we might need to convert it back to timestamp if possible,
        // but standard use case is "now".
        // The original implementation accepted timeSlice (index), OTPHP usually accepts timestamp.
        // For compatibility with "get current code", we use available methods.

        $totp = TOTP::create($secret);

        if ($timeSlice !== null) {
            // Reverse engineer timestamp from slice if absolutely needed,
            // or just warn that this library manages time automatically.
            // 30 seconds is standard period.
            $timestamp = $timeSlice * 30;
            return $totp->at($timestamp);
        }

        return $totp->now();
    }

    /**
     * Verify a code.
     */
    public function verifyCode(string $secret, string $code, int $discrepancy = 1): bool
    {
        $totp = TOTP::create($secret);
        // Verify with window (discrepancy)
        return $totp->verify($code, null, $discrepancy);
    }

    public function getProvisioningUri(string $secret, string $label): string
    {
        $totp = TOTP::create($secret);
        $totp->setLabel($label);
        return $totp->getProvisioningUri();
    }
}
