<?php

namespace MCAG\SecurityLayer\Arsenal;

/**
 * FirewallOps - Active Defense Module
 * 
 * Handles direct manipulation of the firewall (via .htaccess) to ban hostile IPs.
 * IMPORTS "Neural Fry" logic for permanent neutralization.
 */
class FirewallOps
{
    private string $htaccessPath;

    public function __construct(string $projectRoot)
    {
        $this->htaccessPath = $projectRoot . '/public/.htaccess';
    }

    /**
     * Bans an IP address by adding it to the Deny list in .htaccess.
     * 
     * @param string $ip The IP to ban.
     * @return bool True on success.
     */
    public function banIp(string $ip): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        // Avoid banning localhost or internal IPs to prevent lockout
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return false;
        }

        $entry = "Require not ip $ip\n";

        // Read current content to avoid duplicates
        $content = file_get_contents($this->htaccessPath);
        if ($content === false) {
            return false;
        }

        if (str_contains($content, $entry)) {
            return true; // Already banned
        }

        // Append to the "Block Access" section if it exists, otherwise append to end
        // For safety in this environment, simply appending is safest provided 
        // there is a <RequireAll> block or appropriate directive processing.
        // We will wrap it in a marker for easy management.
        $marker = "# --- HYPER GRID BAN LIST ---";

        if (!str_contains($content, $marker)) {
            $content .= "\n" . $marker . "\n";
        }

        $content = str_replace($marker, $marker . "\n" . $entry, $content);

        return file_put_contents($this->htaccessPath, $content) !== false;
    }

    /**
     * Simulates a "Neural Fry" - aggressive ban + log wipe (handled by AuditTrail).
     * This method just handles the ban part.
     */
    public function neuralFry(string $ip): bool
    {
        // 1. Hard Ban
        return $this->banIp($ip);
        // 2. (Controller will handle log wiping via AuditTrail)
    }

    /**
     * Checks if an IP is currently banned.
     */
    public function isBanned(string $ip): bool
    {
        $content = file_get_contents($this->htaccessPath);
        return str_contains($content, "Require not ip $ip");
    }
}
