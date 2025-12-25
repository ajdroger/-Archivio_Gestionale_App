<?php

namespace FratellanzaMilitare\Service;

class FiscalCodeCalculator
{
    private array $belfioreCodes;
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
    }

    public function calculate(string $nome, string $cognome, string $dataNascita, string $sesso, string $luogo): string
    {
        $nome = $this->normalizeString($nome);
        $cognome = $this->normalizeString($cognome);
        $luogo = strtoupper(trim($luogo));
        $sesso = strtoupper(trim($sesso));

        $code = '';

        // 1. Cognome (3 lettere)
        $code .= $this->extractSurnameCode($cognome);

        // 2. Nome (3 lettere)
        $code .= $this->extractNameCode($nome);

        // 3. Data di Nascita e Sesso (5 caratteri)
        // Anno (2 chars), Mese (1 char), Giorno (2 chars + 40 se donna)
        try {
            $date = new \DateTime($dataNascita);
            $year = $date->format('y');
            $month = $this->months[$date->format('m')];
            $day = (int) $date->format('d');

            if ($sesso === 'F') {
                $day += 40;
            }
            $dayStr = str_pad((string) $day, 2, '0', STR_PAD_LEFT);

            $code .= $year . $month . $dayStr;

        } catch (\Exception $e) {
            return 'ERROR_DATE';
        }

        // 4. Codice Catastale Comune (4 caratteri)
        // STRICT CHECK: Evitiamo fuzzy match pericolosi (es. "RO" -> "ROMA") e fallback silenziosi a Z000 (Estero)
        if (!isset($this->belfioreCodes[$luogo])) {
            throw new \InvalidArgumentException("Comune non trovato nel database: $luogo");
        }
        $belfiore = $this->belfioreCodes[$luogo];
        $code .= $belfiore;

        // 5. Carattere di Controllo (CIN)
        $code .= $this->calculateControlChar($code);

        return $code;
    }

    private function normalizeString(string $s): string
    {
        return preg_replace('/[^A-Z]/', '', strtoupper(iconv('UTF-8', 'ASCII//TRANSLIT', $s)));
    }

    private function extractSurnameCode(string $s): string
    {
        $consonants = $this->getConsonants($s);
        $vowels = $this->getVowels($s);
        $combined = $consonants . $vowels . 'XXX';
        return substr($combined, 0, 3);
    }

    private function extractNameCode(string $s): string
    {
        $consonants = $this->getConsonants($s);

        if (strlen($consonants) >= 4) {
            // Per il nome, se ci sono 4 o più consonanti, prendiamo la 1a, 3a e 4a
            return $consonants[0] . $consonants[2] . $consonants[3];
        }

        $vowels = $this->getVowels($s);
        $combined = $consonants . $vowels . 'XXX';
        return substr($combined, 0, 3);
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
        if (strlen($partialCode) !== 15) {
            return 'X';
        }

        $sum = 0;
        for ($i = 0; $i < 15; $i++) {
            $char = $partialCode[$i];
            if (($i + 1) % 2 != 0) { // Dispari (1st, 3rd... in 1-based index) -> 0, 2... in 0-based
                $sum += $this->oddValues[$char] ?? 0;
            } else { // Pari
                $sum += $this->evenValues[$char] ?? 0;
            }
        }

        $remainder = $sum % 26;
        return chr(65 + $remainder); // 65 = 'A'
    }
}
