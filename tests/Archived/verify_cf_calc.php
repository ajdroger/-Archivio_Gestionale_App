<?php

require __DIR__ . '/../vendor/autoload.php';

use MCAG\Service\FiscalCodeCalculator;

$calc = new FiscalCodeCalculator();

// Test Case: Mario Rossi, 01/01/1980, M, Roma (H501)
// Expected: RSSMRA80A01H501Z
$nome = 'Mario';
$cognome = 'Rossi';
$data = '1980-01-01';
$sesso = 'M';
$luogo = 'Roma';

echo "Testing Calculation for: $nome $cognome, $data, $sesso, $luogo\n";

$cf = $calc->calculate($nome, $cognome, $data, $sesso, $luogo);

echo "Result: $cf\n";

if ($cf === 'RSSMRA80A01H501U') {
    echo "SUCCESS: Checksum matches.\n";
} else {
    echo "FAILURE: Expected RSSMRA80A01H501U, got $cf\n";
}

// Test Case 2: Maria Verdi, 15/05/1990, F, Milano (F205)
// VRDMRA90E55F205... Checksum to be calc.
// Verdi -> VRD, Maria -> MRA, 90, E (May), 55 (15+40), F205
// VRDMRA90E55F205... lets check logic
echo "\nTesting Calculation for: Maria Verdi, 1990-05-15, F, Milano\n";
$cf2 = $calc->calculate('Maria', 'Verdi', '1990-05-15', 'F', 'Milano');
echo "Result: $cf2\n";

