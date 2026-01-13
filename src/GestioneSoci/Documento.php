<?php

namespace MCAG\GestioneSoci;

use MCAG\Enum\StatoDocumento;
use DateTime;

/**
 * Classe base astratta per tutti i documenti del sistema.
 * 
 * Definisce le proprietà comuni (ID univoco, nome file, hash di integrità, stato, data caricamento).
 * Fornisce metodi per metadati e verifica integrità.
 */
abstract class Documento
{
    public string $IdUnivoco; // UUID per identificazione univoca
    public string $NomeFile; // Nome originale del file
    public string $HashSHA256; // Hash per verifica integrità (anti-tampering)
    public StatoDocumento $Stato; // Enum stato (VALIDATO, IN_ATTESA, etc.)
    public DateTime $DataCaricamento;

    /**
     * Restituisce i metadati essenziali in formato JSON.
     * Utile per API e serializzazione leggera.
     * 
     * @return string JSON
     */
    public function getMetadati(): string
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
     * Verifica l'integrità del documento confrontando l'hash.
     * 
     * @param string|null $content Contenuto del file binario per il ricalcolo dell'hash.
     * @return bool True se l'hash corrisponde (documento integro), False altrimenti.
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


