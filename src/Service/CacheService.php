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
     * Get cached value
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
     * Set cached value
     */
    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        return $this->redis->set($this->prefix . $key, $value, $ttl);
    }

    /**
     * Delete cached value
     */
    public function delete(string $key): bool
    {
        return $this->redis->delete($this->prefix . $key);
    }

    /**
     * Cache-aside pattern
     */
    public function remember(string $key, Closure $callback, int $ttl = 3600): mixed
    {
        return $this->redis->remember($this->prefix . $key, $callback, $ttl);
    }

    /**
     * Invalidate all cache entries with specific tag
     */
    public function invalidateTag(string $tag): int
    {
        return $this->redis->deletePattern($this->prefix . $tag . ':*');
    }

    /**
     * Cache per lista soci con filtri
     */
    public function rememberSociList(array $filters, Closure $callback, int $ttl = 300): mixed
    {
        $key = 'soci:list:' . md5(serialize($filters));
        return $this->remember($key, $callback, $ttl);
    }

    /**
     * Cache per statistiche dashboard
     */
    public function rememberStats(string $type, Closure $callback, int $ttl = 900): mixed
    {
        $key = 'stats:' . $type;
        return $this->remember($key, $callback, $ttl);
    }

    /**
     * Invalidate soci cache
     */
    public function invalidateSoci(): int
    {
        return $this->invalidateTag('soci');
    }

    /**
     * Invalidate stats cache
     */
    public function invalidateStats(): int
    {
        return $this->invalidateTag('stats');
    }

    /**
     * Get cache statistics
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
     * Clear all application cache
     */
    public function clearAll(): bool
    {
        return $this->redis->flush();
    }
}
