<?php

namespace FratellanzaMilitare\Service;

class FileEmailService implements EmailServiceInterface
{
    private string $logFile;

    public function __construct(string $logPath)
    {
        $this->logFile = $logPath;
    }

    public function send(string $to, string $subject, string $body, array $attachments = []): bool
    {
        $date = date('Y-m-d H:i:s');
        $count = count($attachments);
        $logEntry = "[$date] TO: $to | SUBJECT: $subject | ATTACHMENTS: $count | BODY: " . str_replace("\n", " ", $body) . PHP_EOL;

        return (file_put_contents($this->logFile, $logEntry, FILE_APPEND) !== false);
    }
}
