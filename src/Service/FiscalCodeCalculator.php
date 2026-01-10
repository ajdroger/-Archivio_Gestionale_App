<?php

namespace FratellanzaMilitare\Service;

/**
 * Servizio per il calcolo del Codice Fiscale italiano.
 * 
 * Implementa l'algoritmo ufficiale (D.M. 12/03/1974).
 * Include normalizzazione robusta per i nomi dei comuni (Belfiore lookup).
 */
class FiscalCodeCalculator
{
    private array $belfioreCodes;
    // Mappa ottimizzata: Chiave Normalizzata (solo A-Z) -> Codice Belfiore
    private array $normalizedBelfioreMap = [];

    private array $months = [
        '01' => 'A',
        '02' => 'B',
        '03' => 'C',
        '04' => 'D',
        '05' => 'E',
        '06' => 'H',
        '07' => 'L',
        '08' => 'M',
        '09' => 'P',
        '10' => 'R',
        '11' => 'S',
        '12' => 'T'
    ];

    // Valori per calcolo CIN (omessi per brevità, standard)
    private array $oddValues = [
        '0' => 1,
        '1' => 0,
        '2' => 5,
        '3' => 7,
        '4' => 9,
        '5' => 13,
        '6' => 15,
        '7' => 17,
        '8' => 19,
        '9' => 21,
        'A' => 1,
        'B' => 0,
        'C' => 5,
        'D' => 7,
        'E' => 9,
        'F' => 13,
        'G' => 15,
        'H' => 17,
        'I' => 19,
        'J' => 21,
        'K' => 2,
        'L' => 4,
        'M' => 18,
        'N' => 20,
        'O' => 11,
        'P' => 3,
        'Q' => 6,
        'R' => 8,
        'S' => 12,
        'T' => 14,
        'U' => 16,
        'V' => 10,
        'W' => 22,
        'X' => 25,
        'Y' => 24,
        'Z' => 23
    ];

    private array $evenValues = [
        '0' => 0,
        '1' => 1,
        '2' => 2,
        '3' => 3,
        '4' => 4,
        '5' => 5,
        '6' => 6,
        '7' => 7,
        '8' => 8,
        '9' => 9,
        'A' => 0,
        'B' => 1,
        'C' => 2,
        'D' => 3,
        'E' => 4,
        'F' => 5,
        'G' => 6,
        'H' => 7,
        'I' => 8,
        'J' => 9,
        'K' => 10,
        'L' => 11,
        'M' => 12,
        'N' => 13,
        'O' => 14,
        'P' => 15,
        'Q' => 16,
        'R' => 17,
        'S' => 18,
        'T' => 19,
        'U' => 20,
        'V' => 21,
        'W' => 22,
        'X' => 23,
        'Y' => 24,
        'Z' => 25
    ];

    public function __construct()
    {
        $path = __DIR__ . '/../../resources/belfiore.json';
        if (file_exists($path)) {
            $this->belfioreCodes = json_decode(file_get_contents($path), true) ?? [];
        } else {
            $this->belfioreCodes = [];
        }

        // Pre-calcolo mappa normalizzata per lookup veloce e robusto
        foreach ($this->belfioreCodes as $place => $code) {
            $normalizedKey = $this->stripToEssentials($place);
            // Gestiamo collisioni potenziali (molto rare su nomi città)
            // In caso di collisione, manteniamo il primo o loggiamo warning (qui semplifichiamo)
            $this->normalizedBelfioreMap[$normalizedKey] = $code;
        }
    }

    public function calculate(string $nome, string $cognome, string $dataNascita, string $sesso, string $luogo): string
    {
        $nome = $this->normalizeString($nome);
        $cognome = $this->normalizeString($cognome);

        // 1. Cognome
        $code = $this->extractSurnameCode($cognome);
        // 2. Nome
        $code .= $this->extractNameCode($nome);

        // 3. Data e Sesso
        $sesso = strtoupper(trim($sesso));
        if (!in_array($sesso, ['M', 'F'])) {
            throw new \InvalidArgumentException("Sesso non valido: $sesso");
        }

        try {
            $date = new \DateTime($dataNascita);
            $year = $date->format('y');
            $month = $this->months[$date->format('m')];
            $day = (int) $date->format('d');
            if ($sesso === 'F')
                $day += 40;
            $code .= $year . $month . str_pad((string) $day, 2, '0', STR_PAD_LEFT);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Data di nascita non valida: $dataNascita");
        }

        // 4. Codice Catastale con Lookup Robusto
        // Normalizziamo input allo stesso modo delle chiavi mappa (solo A-Z)
        $luogoClean = $this->stripToEssentials($luogo);

        if (isset($this->normalizedBelfioreMap[$luogoClean])) {
            $code .= $this->normalizedBelfioreMap[$luogoClean];
        } else {
            // Se fallisce, proviamo lookup diretto su array originale (case sensitive/insensitive) per sicurezza
            // Ma stripToEssentials dovrebbe coprire tutto.

            // Suggerimenti Debug
            $suggestions = [];
            foreach (array_keys($this->belfioreCodes) as $k) {
                if (str_contains($this->stripToEssentials($k), substr($luogoClean, 0, 5))) {
                    $suggestions[] = $k;
                }
                if (count($suggestions) > 3)
                    break;
            }
            $hint = empty($suggestions) ? '' : " Forse intendevi: " . implode(", ", $suggestions);

            throw new \InvalidArgumentException("Comune non trovato: '$luogo' (cercato come '$luogoClean').$hint");
        }

        // 5. CIN
        $code .= $this->calculateControlChar($code);

        return $code;
    }

    /**
     * Riduce una stringa ai minimi termini: SOLO lettere A-Z.
     * Rimuove spazi, accenti, apostrofi, numeri.
     * Esempio: "Reggio nell'Emilia" -> "REGGIONELLEMILIA"
     * Esempio: "Forlì" -> "FORLI"
     */
    private function stripToEssentials(string $s): string
    {
        $s = strtoupper(trim($s));
        // Transliterazione Accenti
        $s = strtr($s, [
            'À' => 'A',
            'Á' => 'A',
            'È' => 'E',
            'É' => 'E',
            'Ì' => 'I',
            'Í' => 'I',
            'Ò' => 'O',
            'Ó' => 'O',
            'Ù' => 'U',
            'Ú' => 'U',
            'à' => 'A',
            'á' => 'A',
            'è' => 'E',
            'é' => 'E',
            'ì' => 'I',
            'í' => 'I',
            'ò' => 'O',
            'ó' => 'O',
            'ù' => 'U',
            'ú' => 'U'
        ]);
        if (function_exists('iconv')) {
            $conv = iconv('UTF-8', 'ASCII//TRANSLIT', $s);
            if ($conv !== false)
                $s = $conv;
        }
        // Rimuovi tutto tranne lettere A-Z
        return preg_replace('/[^A-Z]/', '', $s);
    }

    private function normalizeString(string $s): string
    {
        return $this->stripToEssentials($s);
    }

    private function extractSurnameCode(string $s): string
    {
        $consonants = $this->getConsonants($s);
        $vowels = $this->getVowels($s);
        return substr($consonants . $vowels . 'XXX', 0, 3);
    }

    private function extractNameCode(string $s): string
    {
        $consonants = $this->getConsonants($s);
        if (strlen($consonants) >= 4) {
            return $consonants[0] . $consonants[2] . $consonants[3];
        }
        $vowels = $this->getVowels($s);
        return substr($consonants . $vowels . 'XXX', 0, 3);
    }

    private function getConsonants(string $s): string
    {
        return preg_replace('/[AEIOU]/', '', $s);
    }

    private function getVowels(string $s): string
    {
        return preg_replace('/[^AEIOU]/', '', $s);
    }

    private function calculateControlChar(string $partialCode): string
    {
        if (strlen($partialCode) !== 15)
            return 'X';
        $sum = 0;
        for ($i = 0; $i < 15; $i++) {
            $char = $partialCode[$i];
            $val = (($i + 1) % 2 != 0) ? ($this->oddValues[$char] ?? 0) : ($this->evenValues[$char] ?? 0);
            $sum += $val;
        }
        return chr(65 + ($sum % 26));
    }
}
