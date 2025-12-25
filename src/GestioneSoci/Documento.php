<?php

namespace FratellanzaMilitare\GestioneSoci;

use FratellanzaMilitare\Enum\StatoDocumento;
use DateTime;

abstract class Documento
{
    public string $IdUnivoco; // UUID
    public string $NomeFile;
    public string $HashSHA256;
    public StatoDocumento $Stato;
    public DateTime $DataCaricamento;

    public function getMetadati(): string // Ritorna il JSON codificato
    {
        return json_encode([
            'id' => $this->IdUnivoco,
            'name' => $this->NomeFile,
            'hash' => $this->HashSHA256,
            'status' => $this->Stato->name, // Assuming Enum
            'date' => $this->DataCaricamento->format('Y-m-d H:i:s')
        ]) ?: '{}';
    }

    /**
     * @param string|null $content Contenuto opzionale con cui effettuare la verifica. Se nullo, l'implementazione potrebbe basarsi su controlli dello storage esterno.
     */
    public function verificaIntegrita(?string $content = null): bool
    {
        if ($content === null) {
            return false; // Impossibile verificare senza il contenuto
        }

        $calcolato = hash('sha256', $content);
        return hash_equals($this->HashSHA256, $calcolato);
    }
}
