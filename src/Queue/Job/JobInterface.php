<?php

namespace FratellanzaMilitare\Queue\Job;

interface JobInterface
{
    /**
     * Execute the job.
     */
    public function handle($container): void;
}
