<?php

namespace FratellanzaMilitare\Event\Events;

use FratellanzaMilitare\Event\AbstractEvent;

/**
 * Class SocioCreatedEvent
 * 
 * Triggered when a new Socio is successfully persisted.
 * 
 * @package FratellanzaMilitare\Event\Events
 */
class SocioCreatedEvent extends AbstractEvent
{
    public function __construct(
        public readonly int $socioId,
        public readonly string $codiceFiscale,
        public readonly string $nome,
        public readonly string $cognome,
        public readonly string $createdBy
    ) {
        parent::__construct();
    }
}
