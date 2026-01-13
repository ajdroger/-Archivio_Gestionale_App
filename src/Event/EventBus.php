<?php

namespace MCAG\Event;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Class EventBus
 * 
 * Simple synchronous Event Bus implementation.
 * Dispatches events to registered listeners in order.
 * 
 * @package MCAG\Event
 */
class EventBus implements EventBusInterface
{
    /**
     * @var array<string, array<callable|ListenerInterface>>
     */
    private array $listeners = [];

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function subscribe(string $eventClass, callable|ListenerInterface $listener): void
    {
        $this->listeners[$eventClass][] = $listener;
    }

    /**
     * {@inheritDoc}
     */
    public function dispatch(object $event): void
    {
        $eventClass = get_class($event);

        if (!isset($this->listeners[$eventClass])) {
            // No listeners registered for this event
            return;
        }

        foreach ($this->listeners[$eventClass] as $listener) {
            if ($event instanceof AbstractEvent && $event->isPropagationStopped()) {
                break;
            }

            try {
                if ($listener instanceof ListenerInterface) {
                    $listener->handle($event);
                } elseif (is_callable($listener)) {
                    $listener($event);
                }
            } catch (Throwable $e) {
                // Log error but don't crash the application flow if a listener fails
                // Unless it's critical, but generally events are side-effects.
                $this->logger->error("EventBus Listener Error for {$eventClass}: " . $e->getMessage(), [
                    'exception' => $e,
                    'event_data' => serialize($event) // Be careful with sensitive data
                ]);
            }
        }
    }
}


