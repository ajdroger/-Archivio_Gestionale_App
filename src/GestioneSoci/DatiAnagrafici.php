<?php

namespace FratellanzaMilitare\GestioneSoci;

use DateTime;

/**
 * Value Object
 */
class DatiAnagrafici
{
    public string $Nome;
    public string $Cognome;
    public DateTime $DataNascita;
    public string $Indirizzo;
    public string $Email;
    public string $Telefono = '';
}
