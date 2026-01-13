<?php

require __DIR__ . '/../vendor/autoload.php';

use MCAG\Service\ValidationService;

$val = new ValidationService();

// Test Cases
$cases = [
    'RSSMRA80A01H501Z' => true,  // Valid Standard
    'RSSMRA80A01H501' => false,  // Short
    'RSSMRA80A01H5011' => false, // Invalid Check Char (digit)
    'RSSMRA80A01H501$' => false, // Special char
    '1234567890123456' => false, // All digits (Old regex allowed A-Z0-9, but likely failed logic. New regex prevents structure violation)
    'RSSMRA80A41H501Z' => true,  // Valid Female
    'RSSMRA80M01H501Z' => true,  // Valid Omocodia Month? No M is valid month? NO. Month is ABCDEHLMPRST. M is valid (August).
    'RSSMRA80A01Z000A' => false, // Invalid City Code (Z000 strict regex expects 3 digits/omocodia? No, regex is [0-9LMN...]{3}).
    // "Z000" matches [A-Z][0-9...]{3} so it might pass regex but fail Belfiore lookup.
    // Regex is purely structural.
];

echo "Testing Strict Regex Validation:\n";
foreach ($cases as $cf => $expected) {
    echo "Testing $cf... ";
    $result = $val->isValidCodiceFiscale($cf);
    if ($result === $expected) {
        echo "PASS\n";
    } else {
        echo "FAIL (Expected " . ($expected ? 'TRUE' : 'FALSE') . ", got " . ($result ? 'TRUE' : 'FALSE') . ")\n";
    }
}
