<?php

declare(strict_types=1);

namespace MCAG\Jobs;

/**
 * Job Interface
 * 
 * Tutti i job devono implementare questa interface.
 */
interface JobInterface
{
    /**
     * Handle job execution
     */
    public function handle(): void;

    /**
     * Get job payload for serialization
     */
    public function getPayload(): array;

    /**
     * Get job queue name
     */
    public function getQueue(): string;

    /**
     * Get max retry attempts
     */
    public function getMaxAttempts(): int;

    /**
     * Get delay before retry (in seconds)
     */
    public function getRetryDelay(): int;
}


