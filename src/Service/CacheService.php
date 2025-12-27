<?php

declare(strict_types=1);

namespace FratellanzaMilitare\Service;

use Closure;

/**
 * Application Cache Service
 * 
 * High-level caching service con tag support e invalidation strategies.
 * Usa RedisService come backend ma fornisce API application-specific.
 */
/**
 * Servizio di astrazione per la gestione della Cache (Redis).
 * 
 * Fornisce metodi typed per operazioni comuni (get, set, remember) e pattern "cache-aside".
 * Gestisce statistiche di hit/miss rate per monitoraggio performance.
 */
class CacheService
{
    private RedisService $redis;
    private string $prefix;
    private array $stats = [
        'hits' => 0,
        'misses' => 0,
    ];

    public function __construct(RedisService $redis, string $prefix = 'fm:cache:')
    {
        $this->redis = $redis;
        $this->prefix = $prefix;
    }

    /**
     * Recupera un valore dalla cache.
     * Aggiorna le statistiche interne di hit/miss.
     */
    public function get(string $key): mixed
    {
        $value = $this->redis->get($this->prefix . $key);

        if ($value !== null) {
            $this->stats['hits']++;
        } else {
            $this->stats['misses']++;
        }

        return $value;
    }

    /**
     * Imposta un valore in cache con TTL.
     */
    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        return $this->redis->set($this->prefix . $key, $value, $ttl);
    }

    /**
     * Rimuove un elemento dalla cache.
     */
    public function delete(string $key): bool
    {
        return $this->redis->delete($this->prefix . $key);
    }

    /**
     * Pattern Cache-Aside: restituisce (o calcola e salva) un valore.
     * 
     * Se la chiave esiste, la restituisce. Altrimenti esegue la callback,
     * salva il risultato in cache e lo restituisce.
     * 
     * @param string $key Chiave cache
     * @param Closure $callback Funzione per calcolare il valore in caso di miss
     * @param int $ttl Time to live in secondi
     */
    public function remember(string $key, Closure $callback, int $ttl = 3600): mixed
    {
        return $this->redis->remember($this->prefix . $key, $callback, $ttl);
    }

    /**
     * Invalida tutte le chiavi che iniziano con un determinato tag/prefisso.
     */
    public function invalidateTag(string $tag): int
    {
        return $this->redis->deletePattern($this->prefix . $tag . ':*');
    }

    /**
     * Wrapper specifico per caching della lista soci (filtri included).
     */
    public function rememberSociList(array $filters, Closure $callback, int $ttl = 300): mixed
    {
        $key = 'soci:list:' . md5(serialize($filters));
        return $this->remember($key, $callback, $ttl);
    }

    /**
     * Wrapper specifico per caching delle statistiche dashboard.
     */
    public function rememberStats(string $type, Closure $callback, int $ttl = 900): mixed
    {
        $key = 'stats:' . $type;
        return $this->remember($key, $callback, $ttl);
    }

    /**
     * Invalida la cache relativa ai soci (es. dopo modifica).
     */
    public function invalidateSoci(): int
    {
        return $this->invalidateTag('soci');
    }

    /**
     * Invalida la cache delle statistiche.
     */
    public function invalidateStats(): int
    {
        return $this->invalidateTag('stats');
    }

    /**
     * Restituisce le statistiche di utilizzo della cache (Hit Rate).
     */
    public function getStats(): array
    {
        $total = $this->stats['hits'] + $this->stats['misses'];
        $hitRate = $total > 0 ? ($this->stats['hits'] / $total) * 100 : 0;

        return [
            'hits' => $this->stats['hits'],
            'misses' => $this->stats['misses'],
            'total' => $total,
            'hit_rate' => round($hitRate, 2) . '%',
            'redis_info' => $this->redis->info(),
        ];
    }

    /**
     * Svuota completamente la cache applicativa (FlushDB).
     */
    public function clearAll(): bool
    {
        return $this->redis->flush();
    }
}
