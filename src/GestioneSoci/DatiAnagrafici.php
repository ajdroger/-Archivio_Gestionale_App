<?php

declare(strict_types=1);

namespace FratellanzaMilitare\GestioneSoci;

use DateTime;

/**
 * Value Object
 */
/**
 * Value Object che raggruppa le informazioni anagrafiche di base.
 * 
 * Include Nome, Cognome, Data Nascita (DateTime), Indirizzo, Email e Telefono.
 * Non ha identità propria, vive all'interno dell'entità Socio.
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
