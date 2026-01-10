<?php

declare(strict_types=1);

namespace FratellanzaMilitare\GestioneSoci;

use DateTime;

/**
 * Value Object che raggruppa le informazioni anagrafiche di base.
 */
class DatiAnagrafici
{
    public string $Nome;
    public string $Cognome;
    public DateTime $DataNascita;
    public ?string $LuogoNascita = null; // NEW
    public ?string $Sesso = null; // NEW (M/F)
    public ?string $StatoCivile = null; // NEW
    public string $Indirizzo;
    public string $Email;
    public string $Telefono = '';

    // Campi Civili Aggiuntivi
    public ?string $TitoloStudio = null; // NEW
    public ?string $Professione = null; // NEW
}
