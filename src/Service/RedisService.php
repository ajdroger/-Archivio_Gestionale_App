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
            'timeout' => 1.0, // Fail fast
        ];

        try {
            $this->client = new Client($redisConfig);
            $this->client->connect();
        } catch (\Throwable $e) {
            $this->handleFailure($e, "connection/init");
        }
    }

    private function handleFailure(\Throwable $e, string $context): void
    {
        // Disable Redis for the rest of the request to prevent cascading errors
        $this->enabled = false;
        // Log to system log, but ensure it doesn't leak to stdout if possible.
        // In some setups, error_log goes to stdout. We can silence it here if needed,
        // or just rely on the fact that subsequent calls won't trigger it.
        // error_log("Redis $context failed (Disabling): " . $e->getMessage()); 
    }

    public function get(string $key): mixed
    {
        if (!$this->enabled)
            return null;

        try {
            $value = $this->client->get($key);
            return $value ? unserialize($value) : null;
        } catch (\Exception $e) {
            $this->handleFailure($e, "GET");
            return null;
        }
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (!$this->enabled)
            return false;

        try {
            $serialized = serialize($value);
            if ($ttl !== null) {
                return (bool) $this->client->setex($key, $ttl, $serialized);
            }
            return (bool) $this->client->set($key, $serialized);
        } catch (\Exception $e) {
            $this->handleFailure($e, "SET");
            return false;
        }
    }

    public function delete(string $key): bool
    {
        if (!$this->enabled)
            return false;

        try {
            return (bool) $this->client->del([$key]);
        } catch (\Exception $e) {
            $this->handleFailure($e, "DELETE");
            return false;
        }
    }

    public function deletePattern(string $pattern): int
    {
        if (!$this->enabled)
            return 0;

        try {
            $keys = $this->client->keys($pattern);
            if (empty($keys))
                return 0;
            return $this->client->del($keys);
        } catch (\Exception $e) {
            $this->handleFailure($e, "DELETE PATTERN");
            return 0;
        }
    }

    public function flush(): bool
    {
        if (!$this->enabled)
            return false;

        try {
            return (bool) $this->client->flushdb();
        } catch (\Exception $e) {
            $this->handleFailure($e, "FLUSH");
            return false;
        }
    }

    public function increment(string $key, int $by = 1): int
    {
        if (!$this->enabled)
            return 0;

        try {
            return (int) $this->client->incrby($key, $by);
        } catch (\Exception $e) {
            $this->handleFailure($e, "INCREMENT");
            return 0;
        }
    }

    public function expire(string $key, int $seconds): bool
    {
        if (!$this->enabled)
            return false;

        try {
            return (bool) $this->client->expire($key, $seconds);
        } catch (\Exception $e) {
            $this->handleFailure($e, "EXPIRE");
            return false;
        }
    }

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

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

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
            $this->handleFailure($e, "INFO");
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}


