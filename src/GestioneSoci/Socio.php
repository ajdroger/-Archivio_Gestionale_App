<?php

namespace MCAG\GestioneSoci;

use MCAG\Enum\StatoIscrizione;

/**
 * Entità principale del sistema: il Socio.
 * Aggregate Root esteso con profilo militare e sanitario.
 */
class Socio
{
    public string $CodiceFiscale; // ID Univoco naturale
    public string $Matricola; // ID interno generato
    public DatiAnagrafici $DatiPersonali;
    public StatoIscrizione $Stato;

    /** @var Documento[] Elenco polimorfico dei documenti */
    public array $DocumentiAssociati = [];

    // Ottimizzazione performance
    public ?bool $IsMorosoPrecalculated = null;

    // --- Profilo Militare ---
    public ?string $Grado = null;
    public ?string $CorpoAppartenenza = null;
    public ?\DateTime $DataArruolamento = null;
    public ?\DateTime $DataCongedo = null;

    // --- Profilo Sanitario & Emergenze ---
    public ?string $GruppoSanguigno = null;
    public ?string $NoteMediche = null;
    public ?string $ContattoEmergenza = null;

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
                if ($doc->AnnoSolare === $annoCorrente && $doc->Stato === \MCAG\Enum\StatoDocumento::VALIDATO) {
                    return false; // Pagato e validato
                }
            }
        }

        return true;
    }
}


