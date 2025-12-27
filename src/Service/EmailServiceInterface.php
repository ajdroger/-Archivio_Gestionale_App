<?php

namespace FratellanzaMilitare\Service;

/**
 * Interfaccia per il servizio di invio Email.
 * 
 * Astrae l'implementazione specifica (SMTP, File, ServiceProvider) dal dominio.
 */
interface EmailServiceInterface
{
    /**
     * Invia una email.
     * 
     * @param string $to Destinatario
     * @param string $subject Oggetto
     * @param string $body Corpo del messaggio
     * @param array $attachments Elenco percorsi allegati
     * @return bool Esito invio
     */
    public function send(string $to, string $subject, string $body, array $attachments = []): bool;
}
