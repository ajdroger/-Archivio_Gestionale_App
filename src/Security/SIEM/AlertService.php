<?php

namespace FratellanzaMilitare\Security\SIEM;

/**
 * Servizio Placeholder per integrazione SIEM / Breach Detection.
 * 
 * TODO: Decommentare e integrare con API esterne (es. Splunk, ELK, AWS GuardDuty)
 * quando il servizio sarà acquistato/attivato.
 */
class AlertService
{
    private bool $enabled;

    public function __construct()
    {
        $this->enabled = filter_var($_ENV['SIEM_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    public function triggerAlert(string $severity, string $message, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        // --- INTEGRAZIONE SIEM ---
        /*
        $payload = [
            'severity' => $severity, // HIGH, CRITICAL, MEDIUM
            'timestamp' => date('c'),
            'message' => $message,
            'context' => $context,
            'environment' => $_ENV['APP_ENV'] ?? 'unknown'
        ];

        // Esempio: Inviare a webhook sicuro
        // $client->post('https://siem-ingest.fratellanza.it/api/v1/alert', ['json' => $payload]);
        */
    }
}
