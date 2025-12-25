<?php

namespace FratellanzaMilitare\Debug;

class QueryLogger
{
    private string $logFile;

    public function __construct(string $logPath = __DIR__ . '/../../debug_queries.log')
    {
        $this->logFile = $logPath;
    }

    public function log(string $query, array $params = [], float $executionTime = 0): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $paramsStr = json_encode($params);
        $entry = "[{$timestamp}] [{$executionTime}s] QUERY: {$query} | PARAMS: {$paramsStr}\n";

        file_put_contents($this->logFile, $entry, FILE_APPEND);
    }

    public function clearLog(): void
    {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }
    }
}
