<?php

declare(strict_types=1);

namespace FratellanzaMilitare\Jobs;

/**
 * Abstract Base Job
 * 
 * Classe base astratta con implementazione comune per tutti i job.
 */
abstract class AbstractJob implements JobInterface
{
    protected string $queue = 'default';
    protected int $maxAttempts = 3;
    protected int $retryDelay = 60; // seconds

    public function getQueue(): string
    {
        return $this->queue;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function getRetryDelay(): int
    {
        return $this->retryDelay;
    }

    /**
     * Default payload - override if needed
     */
    public function getPayload(): array
    {
        return [
            'class' => static::class,
            'data' => $this->getJobData(),
        ];
    }

    /**
     * Get job-specific data for payload
     */
    abstract protected function getJobData(): array;
}
