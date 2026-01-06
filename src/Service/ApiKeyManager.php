<?php
declare(strict_types=1);

namespace FratellanzaMilitare\Service;

use PDO;
use FratellanzaMilitare\SecurityLayer\AuditTrail;

/**
 * API Key Management Service
 * 
 * Gestisce la creazione, revoca e rotazione delle API keys.
 * Le chiavi sono generate in modo sicuro e memorizzate con hash.
 * 
 * @package FratellanzaMilitare\Service
 */
class ApiKeyManager
{
    private const KEY_LENGTH = 32;

    public function __construct(
        private PDO $pdo,
        private AuditTrail $audit
    ) {
    }

    /**
     * Genera una nuova API key per un utente
     * 
     * @param int $userId ID dell'utente
     * @param string $name Nome descrittivo della chiave
     * @param array $scopes Permessi (es: ['soci:read', 'soci:write'])
     * @param int $rateLimit Richieste per ora (default: 1000)
     * @param ?\DateTimeInterface $expiresAt Data scadenza (null = mai)
     * @return array ['key' => string, 'id' => int, 'prefix' => string]
     */
    public function generateKey(
        int $userId,
        string $name,
        array $scopes,
        int $rateLimit = 1000,
        ?\DateTimeInterface $expiresAt = null
    ): array {
        // Generate secure random key
        $key = $this->generateSecureKey();
        $keyHash = hash('sha256', $key);
        $keyPrefix = substr($key, 0, 8);

        // Store hashed key
        $stmt = $this->pdo->prepare("
            INSERT INTO api_keys (
                user_id, key_hash, key_prefix, name, scopes, rate_limit, expires_at, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $userId,
            $keyHash,
            $keyPrefix,
            $name,
            implode(',', $scopes),
            $rateLimit,
            $expiresAt ? $expiresAt->format('Y-m-d H:i:s') : null
        ]);

        $keyId = (int) $this->pdo->lastInsertId();

        $this->audit->log('API_KEY_CREATED', 'api_key', $keyId, [
            'user_id' => $userId,
            'name' => $name,
            'scopes' => $scopes,
            'rate_limit' => $rateLimit
        ]);

        return [
            'key' => $key, // NEVER store or log the full key again!
            'id' => $keyId,
            'prefix' => $keyPrefix,
            'name' => $name,
            'scopes' => $scopes,
            'rate_limit' => $rateLimit,
            'expires_at' => $expiresAt?->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Revoca una API key
     * 
     * @param int $keyId ID della chiave
     * @param int $userId ID dell'utente (per verificare ownership)
     * @return bool True se revocata, false se non trovata
     */
    public function revokeKey(int $keyId, int $userId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE api_keys
            SET active = 0, updated_at = NOW()
            WHERE id = ? AND user_id = ?
        ");

        $stmt->execute([$keyId, $userId]);

        if ($stmt->rowCount() > 0) {
            $this->audit->log('API_KEY_REVOKED', 'api_key', $keyId, [
                'user_id' => $userId
            ]);
            return true;
        }

        return false;
    }

    /**
     * Ruota una API key (revoca vecchia e crea nuova)
     * 
     * @param int $keyId ID della chiave da ruotare
     * @param int $userId ID dell'utente
     * @return array|null Nuova chiave o null se fallito
     */
    public function rotateKey(int $keyId, int $userId): ?array
    {
        // Get old key info
        $stmt = $this->pdo->prepare("
            SELECT name, scopes, rate_limit, expires_at
            FROM api_keys
            WHERE id = ? AND user_id = ? AND active = 1
        ");
        $stmt->execute([$keyId, $userId]);
        $oldKey = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$oldKey) {
            return null;
        }

        // Create new key with same permissions
        $newKey = $this->generateKey(
            $userId,
            $oldKey['name'] . ' (rotated)',
            explode(',', $oldKey['scopes']),
            (int) $oldKey['rate_limit'],
            $oldKey['expires_at'] ? new \DateTime($oldKey['expires_at']) : null
        );

        // Revoke old key
        $this->revokeKey($keyId, $userId);

        $this->audit->log('API_KEY_ROTATED', 'api_key', $keyId, [
            'new_key_id' => $newKey['id']
        ]);

        return $newKey;
    }

    /**
     * Lista tutte le chiavi di un utente
     * 
     * @param int $userId
     * @param bool $activeOnly Solo chiavi attive
     * @return array
     */
    public function listKeys(int $userId, bool $activeOnly = true): array
    {
        $sql = "
            SELECT id, key_prefix, name, scopes, rate_limit, active, 
                   expires_at, last_used_at, created_at
            FROM api_keys
            WHERE user_id = ?
        ";

        if ($activeOnly) {
            $sql .= " AND active = 1";
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ottieni statistiche uso di una chiave
     * 
     * @param int $keyId
     * @param int $hours Periodo in ore (default: 24)
     * @return array
     */
    public function getKeyStats(int $keyId, int $hours = 24): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_requests,
                COUNT(DISTINCT DATE(created_at)) as active_days,
                MIN(created_at) as first_request,
                MAX(created_at) as last_request,
                AVG(response_time_ms) as avg_response_time
            FROM api_request_tracking
            WHERE api_key_id = ?
            AND created_at > DATE_SUB(NOW(), INTERVAL ? HOUR)
        ");

        $stmt->execute([$keyId, $hours]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Requests per endpoint
        $stmt = $this->pdo->prepare("
            SELECT endpoint, COUNT(*) as count
            FROM api_request_tracking
            WHERE api_key_id = ?
            AND created_at > DATE_SUB(NOW(), INTERVAL ? HOUR)
            GROUP BY endpoint
            ORDER BY count DESC
            LIMIT 10
        ");
        $stmt->execute([$keyId, $hours]);
        $stats['top_endpoints'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $stats;
    }

    /**
     * Cleanup vecchi tracking records (da eseguire via cron)
     * 
     * @param int $daysToKeep Giorni di retention (default: 30)
     * @return int Numero di record eliminati
     */
    public function cleanupOldTracking(int $daysToKeep = 30): int
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM api_request_tracking
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$daysToKeep]);

        return $stmt->rowCount();
    }

    private function generateSecureKey(): string
    {
        // Generate cryptographically secure random bytes
        $bytes = random_bytes(self::KEY_LENGTH);

        // Convert to base64 and make URL-safe
        $key = rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');

        return $key;
    }
}
