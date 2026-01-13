<?php

namespace MCAG\Queue\Job;

interface JobInterface
{
    /**
     * Execute the job.
     */
    public function handle($container): void;
}


