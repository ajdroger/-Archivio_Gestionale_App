<?php

declare(strict_types=1);

namespace FratellanzaMilitare\Service;

use PDO;
use FratellanzaMilitare\Service\RedisService;

/**
 * Enhanced Health Check Service
 * 
 * Esegue health check completi di database, Redis, storage e queue system.
 */
/**
 * Servizio centralizzato per il monitoraggio della salute del sistema (Health Check).
 * 
 * Esegue test su:
 * - Connettività Database
 * - Disponibilità Redis
 * - Scrivibilità Storage
 * - Stato del sistema di Code
 * 
 * Aggrega i risultati per la dashboard o per API di monitoraggio esterne.
 */
class HealthCheckService
{
    private PDO $pdo;
    private ?RedisService $redis;
    private string $storageDir;
    private ?QueueService $queue;

    public function __construct(
        PDO $pdo,
        ?RedisService $redis = null,
        ?QueueService $queue = null,
        string $storageDir = 'storage'
    ) {
        $this->pdo = $pdo;
        $this->redis = $redis;
        $this->queue = $queue;
        $this->storageDir = $storageDir;
    }

    /**
     * Esegue tutti i health check configurati e restituisce un report aggregato.
     * 
     * @return array Report completo con stato globale, timestamp e dettagli per singolo servizio.
     */
    public function checkAll(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        $allHealthy = !in_array('unhealthy', array_column($checks, 'status'));

        return [
            'status' => $allHealthy ? 'healthy' : 'degraded',
            'version' => '2.0.0',
            'timestamp' => time(),
            'uptime' => $this->getUptime(),
            'checks' => $checks,
        ];
    }

    /**
     * Verifica la connettività al database eseguendo una query semplice (SELECT 1).
     */
    private function checkDatabase(): array
    {
        try {
            $stmt = $this->pdo->query('SELECT 1');
            $result = $stmt->fetchColumn();

            if ($result === '1' || $result === 1) {
                return [
                    'status' => 'healthy',
                    'message' => 'Database connection successful',
                    'response_time_ms' => 0, // TODO: measure actual time
                ];
            }

            return [
                'status' => 'unhealthy',
                'message' => 'Database query failed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica lo stato di Redis (se configurato).
     */
    private function checkRedis(): array
    {
        if ($this->redis === null) {
            return [
                'status' => 'disabled',
                'message' => 'Redis not configured',
            ];
        }

        if (!$this->redis->isEnabled()) {
            return [
                'status' => 'disabled',
                'message' => 'Redis disabled',
            ];
        }

        try {
            $info = $this->redis->info();

            if ($info['status'] === 'connected') {
                return [
                    'status' => 'healthy',
                    'message' => 'Redis connected',
                    'version' => $info['version'] ?? 'unknown',
                    'memory' => $info['used_memory'] ?? 'unknown',
                ];
            }

            return [
                'status' => 'unhealthy',
                'message' => 'Redis connection failed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Redis error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica i permessi di scrittura e lettura sulla directory di storage.
     */
    private function checkStorage(): array
    {
        try {
            $testFile = $this->storageDir . '/health_check_' . time() . '.tmp';
            $testContent = 'health_check';

            if (@file_put_contents($testFile, $testContent) === false) {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Storage not writable',
                ];
            }

            $readContent = @file_get_contents($testFile);
            @unlink($testFile);

            if ($readContent === $testContent) {
                $freeSpace = @disk_free_space($this->storageDir);
                return [
                    'status' => 'healthy',
                    'message' => 'Storage writable',
                    'free_space' => $freeSpace ? $this->formatBytes((int) $freeSpace) : 'unknown',
                ];
            }

            return [
                'status' => 'unhealthy',
                'message' => 'Storage read failed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Storage error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica lo stato del sistema di code (se configurato).
     */
    private function checkQueue(): array
    {
        if ($this->queue === null) {
            return [
                'status' => 'disabled',
                'message' => 'Queue system not configured',
            ];
        }

        try {
            $stats = $this->queue->getStats('default');

            return [
                'status' => 'healthy',
                'message' => 'Queue system operational',
                'stats' => $stats,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Queue error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get application uptime (estimated from session)
     */
    private function getUptime(): string
    {
        // In production, this should track actual application start time
        $uptime = time() - ($_SERVER['REQUEST_TIME'] ?? time());
        return $this->formatDuration($uptime);
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    private function formatDuration(int $seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        $parts = [];
        if ($days > 0)
            $parts[] = "{$days}d";
        if ($hours > 0)
            $parts[] = "{$hours}h";
        if ($minutes > 0)
            $parts[] = "{$minutes}m";
        if ($secs > 0 || empty($parts))
            $parts[] = "{$secs}s";

        return implode(' ', $parts);
    }
}
