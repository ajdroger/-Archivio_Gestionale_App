<?php

namespace MCAG\Queue;

use MCAG\Queue\Job\JobInterface;

interface QueueInterface
{
    /**
     * Push a job onto the queue.
     */
    public function push(JobInterface $job): void;

    /**
     * Pop the next available job from the queue.
     */
    public function pop(): ?JobInterface;
}


