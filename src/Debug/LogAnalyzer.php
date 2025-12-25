<?php

namespace FratellanzaMilitare\Debug;

class LogAnalyzer
{
    private string $logFile;

    public function __construct(string $logFile)
    {
        $this->logFile = $logFile;
    }

    /**
     * Estrae tutte le voci di log correlate a un unico Request ID
     */
    public function getLogsByRequestId(string $requestId): array
    {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $logs = file($this->logFile);
        $results = [];

        foreach ($logs as $line) {
            $data = json_decode($line, true);
            if ($data && isset($data['extra']['request_id']) && $data['extra']['request_id'] === $requestId) {
                $results[] = $data;
            }
        }

        return $results;
    }

    /**
     * Analizza i log per trovare richieste lente o errori ricorrenti
     */
    public function getSummary(int $limit = 100): array
    {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $logs = array_slice(file($this->logFile), -$limit);
        $summary = [
            'total' => count($logs),
            'errors' => 0,
            'warnings' => 0,
            'requests' => []
        ];

        foreach ($logs as $line) {
            $data = json_decode($line, true);
            if (!$data) {
                continue;
            }

            if ($data['level'] >= 400) {
                $summary['errors']++;
            } elseif ($data['level'] >= 300) {
                $summary['warnings']++;
            }

            $reqId = $data['extra']['request_id'] ?? 'unknown';
            if (!isset($summary['requests'][$reqId])) {
                $summary['requests'][$reqId] = 0;
            }
            $summary['requests'][$reqId]++;
        }

        return $summary;
    }
}
