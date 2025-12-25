<?php

namespace FratellanzaMilitare\InfrastrutturaIT;

class OCREngine
{
    public function processaImmagine(string $bitmap): string
    {
        // Mock: Simula l'estrazione del testo da un'immagine
        return "NOME: MARIO\nCOGNOME: ROSSI\nCODICE FISCALE: RSSMRA80A01H501U";
    }

    public function estraiCampiChiave(string $text): array
    {
        // Mock: Analisi semplice basata su regex o split
        $lines = explode("\n", $text);
        $data = [];

        foreach ($lines as $line) {
            if (str_contains($line, ':')) {
                [$key, $val] = explode(':', $line);
                $data[trim($key)] = trim($val);
            }
        }

        return $data;
    }
    public function estraiDatiDaImmagine(string $path): array
    {
        $text = $this->processaImmagine($path);
        $raw = $this->estraiCampiChiave($text);

        // Normalizzazione
        $normalized = [];
        foreach ($raw as $k => $v) {
            $normalized[$k] = $v;
            if ($k === 'CODICE FISCALE') {
                $normalized['CF'] = $v;
            }
        }
        return $normalized;
    }
}
