<?php

namespace FratellanzaMilitare\Service;

/**
 * Implementazione del servizio Email su File (Log).
 * 
 * Utile per ambienti di sviluppo/test dove non si vuole inviare email reali.
 * Scrive i dettagli delle email inviate su un file di log specificato.
 */
class FileEmailService implements EmailServiceInterface
{
    private string $logFile;

    public function __construct(string $logPath)
    {
        $this->logFile = $logPath;
    }

    /**
     * Simula l'invio scrivendo su file.
     */
    public function send(string $to, string $subject, string $body, array $attachments = [], array $headers = []): bool
    {
        $date = date('Y-m-d H:i:s');
        $count = count($attachments);
        $logEntry = "[$date] TO: $to | SUBJECT: $subject | ATTACHMENTS: $count | BODY: " . str_replace("\n", " ", $body) . PHP_EOL;

        return (file_put_contents($this->logFile, $logEntry, FILE_APPEND) !== false);
    }
}
