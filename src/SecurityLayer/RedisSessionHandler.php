<?php
declare(strict_types=1);

namespace MCAG\SecurityLayer;

use Predis\Client as Redis;

class RedisSessionHandler implements \SessionHandlerInterface
{
    private const SESSION_TTL = 3600; // 1 hour
    private const KEY_PREFIX = 'session:';

    public function __construct(private Redis $redis)
    {
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $key = self::KEY_PREFIX . $id;
        $data = $this->redis->get($key);

        return $data !== null ? $data : '';
    }

    public function write(string $id, string $data): bool
    {
        $key = self::KEY_PREFIX . $id;
        $this->redis->setex($key, self::SESSION_TTL, $data);

        return true;
    }

    public function destroy(string $id): bool
    {
        $key = self::KEY_PREFIX . $id;
        $this->redis->del($key);

        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        // Redis handles expiration automatically with SETEX
        return 0;
    }
}


