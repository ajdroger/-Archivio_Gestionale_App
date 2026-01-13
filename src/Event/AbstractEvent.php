<?php

namespace MCAG\Event;

use DateTimeImmutable;

/**
 * Class AbstractEvent
 * 
 * Base class for all domain events.
 * Provides timestamping and unique ID generation (optional).
 * 
 * @package MCAG\Event
 */
abstract class AbstractEvent
{
    private DateTimeImmutable $occurredOn;
    private bool $propagationStopped = false;

    public function __construct()
    {
        $this->occurredOn = new DateTimeImmutable();
    }

    /**
     * Get the time when the event occurred.
     */
    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    /**
     * Check if propagation has been stopped.
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * Stop further propagation of this event to subsequent listeners.
     */
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}


