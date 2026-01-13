<?php

namespace MCAG\Debug;

class LogViewer
{
    private string $logDir;

    public function __construct()
    {
        $this->logDir = __DIR__ . '/../../logs';
    }

    /**
     * Elenca i file di log disponibili
     */
    public function listLogs(): array
    {
        if (!is_dir($this->logDir)) {
            return [];
        }

        $files = glob($this->logDir . '/*.{log,json}', GLOB_BRACE);
        return array_map(function ($file) {
            return [
                'name' => basename($file),
                'size' => round(filesize($file) / 1024, 2) . " KB",
                'last_modified' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }, $files);
    }

    /**
     * Legge le ultime N righe di un file di log
     */
    public function readTail(string $filename, int $lines = 50): string
    {
        $path = realpath($this->logDir . '/' . $filename);

        // Protezione contro directory traversal
        if (!$path || strpos($path, realpath($this->logDir)) !== 0 || !file_exists($path)) {
            return "Errore: File non trovato o accesso negato.";
        }

        $data = file($path);
        if ($data === false) {
            return "Impossibile leggere il file.";
        }

        $tail = array_slice($data, -$lines);
        return implode("", $tail);
    }
}


