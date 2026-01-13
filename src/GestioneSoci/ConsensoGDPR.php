<?php

namespace MCAG\GestioneSoci;

use DateTime;

/**
 * Entità che rappresenta il consenso GDPR di un socio.
 * 
 * Traccia le scelte di privacy (trattamento dati, cessione a terzi, marketing),
 * la data di firma, la versione dell'informativa accettata e l'eventuale revoca.
 * Estende Documento per storicizzazione (se necessario) o per analogia strutturale.
 */
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
     * Aggiorna i consensi e logga l'azione nell'Audit Trail.
     * 
     * @param bool $trattamento Consenso base obbligatorio/facoltativo
     * @param bool $cessione Consenso cessione dati a terzi
     * @param bool $marketing Consenso comunicazioni commerciali
     * @param string $versione Identificativo versione informativa (es. "v2024.1")
     * @param \MCAG\SecurityLayer\UtenteSistema $operatore Chi effettua l'operazione
     */
    public function aggiornaConsensi(
        bool $trattamento,
        bool $cessione,
        bool $marketing,
        string $versione,
        \MCAG\SecurityLayer\UtenteSistema $operatore
    ): void {
        $this->TrattamentoDati = $trattamento;
        $this->CessioneTerzi = $cessione;
        $this->Marketing = $marketing;
        $this->VersioneInformativa = $versione;
        $this->DataFirma = new DateTime();
        $this->Attivo = true;

        \MCAG\SecurityLayer\AuditTrail::getInstance()->logEvento(
            $operatore,
            'GDPR_CONSENT_UPDATE',
            "socio_cf_{$this->IdUnivoco}"
        );
    }

    /**
     * Revoca il consenso GDPR.
     * 
     * Imposta il flag Attivo a false e registra data e motivo della revoca.
     * Questa azione inibisce trattamenti futuri ma mantiene lo storico.
     * 
     * @param string $motivo Ragione della revoca
     * @param \MCAG\SecurityLayer\UtenteSistema $operatore
     */
    public function revoca(string $motivo, \MCAG\SecurityLayer\UtenteSistema $operatore): void
    {
        $this->Attivo = false;
        $this->DataRevoca = new DateTime();
        $this->MotivoRevoca = $motivo;

        \MCAG\SecurityLayer\AuditTrail::getInstance()->logEvento(
            $operatore,
            'GDPR_CONSENT_REVOKE',
            "socio_cf_{$this->IdUnivoco}"
        );
    }
}


