<?php

namespace MCAG\Security\SIEM;

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

        // --- INTEGRAZIONE SIEM (Simulazione / File Fallback) ---
        $payload = [
            'severity' => $severity,
            'timestamp' => date('c'),
            'message' => $message,
            'context' => $context,
            'environment' => $_ENV['APP_ENV'] ?? 'unknown',
            'source_ip' => $_SERVER['REMOTE_ADDR'] ?? 'cli'
        ];

        // In produzione, questo invierebbe una HTTP Request a Splunk/ELK.
        // Qui simuliamo scrivendo su un log dedicato alla sicurezza (audit).
        $logEntry = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $siemLogFile = __DIR__ . '/../../../var/logs/siem_security.log';
        file_put_contents($siemLogFile, $logEntry . PHP_EOL, FILE_APPEND);
    }
}


