<?php

namespace FratellanzaMilitare\GestioneSoci;

use FratellanzaMilitare\Enum\StatoIscrizione;

class Socio
{
    public string $CodiceFiscale;
    public string $Matricola;
    public DatiAnagrafici $DatiPersonali;
    public StatoIscrizione $Stato;

    /** @var Documento[] */
    public array $DocumentiAssociati = [];

    // Ottimizzazione: permetti di iniettare il valore se già calcolato via SQL
    public ?bool $IsMorosoPrecalculated = null;

    public function aggiornaAnagrafica(DatiAnagrafici $nuoviDati): void
    {
        $this->DatiPersonali = $nuoviDati;
    }

    public function aggiungiDocumento(Documento $doc): void
    {
        $this->DocumentiAssociati[] = $doc;
    }

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
