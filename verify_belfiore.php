<?php
$data = json_decode(file_get_contents(__DIR__ . '/resources/belfiore.json'), true);
echo "Total: " . count($data) . "\n";
$tests = ['BAGNO A RIPOLI', 'ROMA', 'AFAGHANISTAN', 'ZIMBABWE'];
foreach ($tests as $t) {
    if (isset($data[$t])) {
        echo "Found $t: " . $data[$t] . "\n";
    } else {
        echo "MISSING $t\n";
    }
}
