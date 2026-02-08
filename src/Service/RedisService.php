<?php

declare(strict_types=1);

namespace MCAG\Service;

use Predis\Client;
use Predis\ClientInterface;
use Closure;

/**
 * Redis Service - Wrapper per Predis client con metodi utility
 * 
 * Gestisce connessione Redis e operazioni comuni come get, set, delete, flush.
 * Supporta caching con TTL e pattern remember per cache-aside.
 */
/**
 * Servizio wrapper per la gestione di Redis.
 * 
 * Implementa un client Redis con fallback (null object pattern) se disabilitato
 * o se la connessione fallisce.
 * Fornisce metodi helper per le operazioni comuni (get, set, increment, expire).
 */
class RedisService
{
    private ClientInterface $client;
    private bool $enabled;

    public function __construct($configOrClient = null)
    {
        // Allow Dependency Injection of Client for Testing
        if ($configOrClient instanceof ClientInterface) {
            $this->client = $configOrClient;
            $this->enabled = true;
            return;
        }

        $this->enabled = !empty(getenv('REDIS_ENABLED')) && getenv('REDIS_ENABLED') === 'true';

        if (!$this->enabled) {
            // Redis disabled, use null object pattern
            return;
        }

        $redisConfig = is_array($configOrClient) ? $configOrClient : [
            'scheme' => getenv('REDIS_SCHEME') ?: 'tcp',
            'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
            'port' => (int) (getenv('REDIS_PORT') ?: 6379),
            'database' => (int) (getenv('REDIS_DB') ?: 0),
        ];

        try {
            $this->client = new Client($redisConfig);
            $this->client->connect();
        } catch (\Throwable $e) { // Catch Error and Exception
            // Fallback to disabled mode if connection fails or class missing
            $this->enabled = false;
            error_log("Redis connection/init failed: " . $e->getMessage());
        }
    }

    /**
     * Recupera un valore da Redis.
     */
    public function get(string $key): mixed
    {
        if (!$this->enabled) {
            return null;
        }

        try {
            $value = $this->client->get($key);
            return $value ? unserialize($value) : null;
        } catch (\Exception $e) {
            error_log("Redis GET error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Imposta un valore in Redis con TTL opzionale.
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            $serialized = serialize($value);

            if ($ttl !== null) {
                return (bool) $this->client->setex($key, $ttl, $serialized);
            }

            return (bool) $this->client->set($key, $serialized);
        } catch (\Exception $e) {
            error_log("Redis SET error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancella una chiave da Redis.
     */
    public function delete(string $key): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            return (bool) $this->client->del([$key]);
        } catch (\Exception $e) {
            error_log("Redis DELETE error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancella tutte le chiavi che corrispondono a un pattern glob.
     */
    public function deletePattern(string $pattern): int
    {
        if (!$this->enabled) {
            return 0;
        }

        try {
            $keys = $this->client->keys($pattern);
            if (empty($keys)) {
                return 0;
            }
            return $this->client->del($keys);
        } catch (\Exception $e) {
            error_log("Redis DELETE PATTERN error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Svuota l'intero database Redis selezionato.
     */
    public function flush(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            return (bool) $this->client->flushdb();
        } catch (\Exception $e) {
            error_log("Redis FLUSH error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Incrementa un contatore atomico (utile per rate limiting).
     */
    public function increment(string $key, int $by = 1): int
    {
        if (!$this->enabled) {
            return 0;
        }

        try {
            return (int) $this->client->incrby($key, $by);
        } catch (\Exception $e) {
            error_log("Redis INCREMENT error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Imposta la scadenza (TTL) su una chiave esistente.
     */
    public function expire(string $key, int $seconds): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            return (bool) $this->client->expire($key, $seconds);
        } catch (\Exception $e) {
            error_log("Redis EXPIRE error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Pattern Cache-Aside: ottiene dalla cache o calcola e salva.
     */
    public function remember(string $key, Closure $callback, int $ttl = 3600): mixed
    {
        // Try to get from cache
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        // Compute value
        $value = $callback();

        // Store in cache
        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * Verifica se Redis è abilitato e connesso.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get Redis connection info
     */
    public function info(): array
    {
        if (!$this->enabled) {
            return ['status' => 'disabled'];
        }

        try {
            $info = $this->client->info();
            return [
                'status' => 'connected',
                'version' => $info['Server']['redis_version'] ?? 'unknown',
                'used_memory' => $info['Memory']['used_memory_human'] ?? 'unknown',
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}


