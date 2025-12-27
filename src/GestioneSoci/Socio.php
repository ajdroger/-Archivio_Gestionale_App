<?php

namespace FratellanzaMilitare\GestioneSoci;

use FratellanzaMilitare\Enum\StatoIscrizione;

/**
 * Entità principale del sistema: il Socio.
 * 
 * Rappresenta un iscritto all'associazione. È un Aggregate Root che contiene:
 * - Dati Anagrafici (Value Object)
 * - Stato Iscrizione
 * - Documenti Associati (Collezione)
 * - Identificativi (CF, Matricola)
 */
class Socio
{
    public string $CodiceFiscale; // ID Univoco naturale
    public string $Matricola; // ID interno generato
    public DatiAnagrafici $DatiPersonali;
    public StatoIscrizione $Stato;

    /** @var Documento[] Elenco polimorfico dei documenti */
    public array $DocumentiAssociati = [];

    // Ottimizzazione: permette di iniettare il valore se già calcolato via SQL per performance
    public ?bool $IsMorosoPrecalculated = null;

    /**
     * Aggiorna i dati anagrafici del socio.
     */
    public function aggiornaAnagrafica(DatiAnagrafici $nuoviDati): void
    {
        $this->DatiPersonali = $nuoviDati;
    }

    /**
     * Associa un nuovo documento al socio.
     */
    public function aggiungiDocumento(Documento $doc): void
    {
        $this->DocumentiAssociati[] = $doc;
    }

    /**
     * Rimuove un documento dalla lista (by ID).
     */
    public function rimuoviDocumento(string $idUnivoco): void
    {
        foreach ($this->DocumentiAssociati as $key => $doc) {
            if ($doc->IdUnivoco === $idUnivoco) {
                unset($this->DocumentiAssociati[$key]);
                // Re-index array to avoid gaps
                $this->DocumentiAssociati = array_values($this->DocumentiAssociati);
                break;
            }
        }
    }

    /**
     * Verifica se il socio è moroso per l'anno corrente.
     * 
     * Un socio è in regola se possiede un ModuloIscrizione valido per l'anno in corso.
     * Se è stato precalcolato (es. da query SQL), usa il valore in cache.
     * 
     * @return bool True se moroso, False se in regola.
     */
    public function verificaMorosita(): bool
    {
        if ($this->IsMorosoPrecalculated !== null) {
            return $this->IsMorosoPrecalculated;
        }

        $annoCorrente = (int) date('Y');

        foreach ($this->DocumentiAssociati as $doc) {
            if ($doc instanceof ModuloIscrizione) {
                if ($doc->AnnoSolare === $annoCorrente && $doc->Stato === \FratellanzaMilitare\Enum\StatoDocumento::VALIDATO) {
                    return false; // Pagato e validato
                }
            }
        }

        return true; // Moroso se non trova iscrizione valida anno corrente
    }
}
