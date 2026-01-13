<?php

namespace MCAG\Event\Listeners;

use MCAG\Event\ListenerInterface;
use MCAG\Event\Events\SocioCreatedEvent;
use Psr\Log\LoggerInterface;

class LogSocioCreationListener implements ListenerInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handle(object $event): void
    {
        if (!$event instanceof SocioCreatedEvent) {
            return;
        }

        $this->logger->info("EVENT: Socio Created", [
            'event_id' => bin2hex(random_bytes(4)), // Simple tracking
            'socio_id' => $event->socioId,
            'cf' => $event->codiceFiscale,
            'name' => "{$event->nome} {$event->cognome}",
            'created_by' => $event->createdBy,
            'occurred_on' => $event->getOccurredOn()->format('Y-m-d H:i:s.u')
        ]);
    }
}


