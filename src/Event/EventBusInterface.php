<?php

namespace MCAG\Event;

/**
 * Interface EventBusInterface
 * 
 * Defines the contract for an Event Dispatcher system.
 * Allows decoupling of event producers from consumers.
 * 
 * @package MCAG\Event
 */
interface EventBusInterface
{
    /**
     * Dispatch an event to all registered listeners.
     * 
     * @param object $event The event object (usually extends AbstractEvent)
     * @return void
     */
    public function dispatch(object $event): void;

    /**
     * Subscribe a listener to a specific event class.
     * 
     * @param string $eventClass The fully qualified class name of the event.
     * @param callable|ListenerInterface $listener The listener to execute.
     * @return void
     */
    public function subscribe(string $eventClass, callable|ListenerInterface $listener): void;
}


