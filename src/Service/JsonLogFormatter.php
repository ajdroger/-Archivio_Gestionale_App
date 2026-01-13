<?php

declare(strict_types=1);

namespace MCAG\Service;

use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;

/**
 * JSON Log Formatter per Structured Logging
 * 
 * Formatta log in JSON con context enrichment automatico.
 */
/**
 * Formattatore di log personalizzato in formato JSON.
 * 
 * Assicura che i log siano strutturati e facilmente parsabili (es. da stack ELK o Viewer JS).
 * Include automaticamente metadati di contesto (IP, URI, Method) se disponibili.
 */
class JsonLogFormatter implements FormatterInterface
{
    private string $applicationName;

    public function __construct(string $applicationName = 'MCAG')
    {
        $this->applicationName = $applicationName;
    }

    /**
     * Formatta il singolo record di log in JSON.
     */
    public function format(LogRecord $record): string
    {
        $data = [
            'timestamp' => $record->datetime->format('Y-m-d\TH:i:s.uP'),
            'level' => $record->level->getName(),
            'message' => $record->message,
            'context' => $record->context,
            'extra' => $record->extra,
            'application' => $this->applicationName,
        ];

        // Add request context if available
        if (!empty($_SERVER['REQUEST_URI'])) {
            $data['request'] = [
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
                'uri' => $_SERVER['REQUEST_URI'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
            ];
        }

        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }

    /**
     * Formatta un batch di record.
     */
    public function formatBatch(array $records): string
    {
        $formatted = '';
        foreach ($records as $record) {
            $formatted .= $this->format($record);
        }
        return $formatted;
    }
}


