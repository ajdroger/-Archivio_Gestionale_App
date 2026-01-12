<?php

namespace FratellanzaMilitare\Event;

/**
 * Interface ListenerInterface
 * 
 * Contract for classes that listen to events.
 * 
 * @package FratellanzaMilitare\Event
 */
interface ListenerInterface
{
    /**
     * Handle the event.
     * 
     * @param object $event
     * @return void
     */
    public function handle(object $event): void;
}
