<?php

namespace FratellanzaMilitare\GestioneSoci;

use DateTime;

class ConsensoGDPR extends Documento
{
    public bool $TrattamentoDati;
    public bool $CessioneTerzi;
    public bool $Marketing;
    public DateTime $DataFirma;
    public string $VersioneInformativa;
    public bool $Attivo = true;
    public ?DateTime $DataRevoca = null;
    public ?string $MotivoRevoca = null;

    /**
     * Aggiorna i consensi e logga l'azione
     */
    public function aggiornaConsensi(
        bool $trattamento,
        bool $cessione,
        bool $marketing,
        string $versione,
        \FratellanzaMilitare\SecurityLayer\UtenteSistema $operatore
    ): void {
        $this->TrattamentoDati = $trattamento;
        $this->CessioneTerzi = $cessione;
        $this->Marketing = $marketing;
        $this->VersioneInformativa = $versione;
        $this->DataFirma = new DateTime();
        $this->Attivo = true;

        \FratellanzaMilitare\SecurityLayer\AuditTrail::getInstance()->logEvento(
            $operatore,
            'GDPR_CONSENT_UPDATE',
            "socio_cf_{$this->IdUnivoco}"
        );
    }

    /**
     * Revoca il consenso GDPR
     */
    public function revoca(string $motivo, \FratellanzaMilitare\SecurityLayer\UtenteSistema $operatore): void
    {
        $this->Attivo = false;
        $this->DataRevoca = new DateTime();
        $this->MotivoRevoca = $motivo;

        \FratellanzaMilitare\SecurityLayer\AuditTrail::getInstance()->logEvento(
            $operatore,
            'GDPR_CONSENT_REVOKE',
            "socio_cf_{$this->IdUnivoco}"
        );
    }
}
