<?php
$urls = [
    'https://raw.githubusercontent.com/opendum/comuni-e-stati-esteri/master/stati_esteri.json',
    'https://raw.githubusercontent.com/opendum/comuni-e-stati-esteri/master/comuni.json'
];

foreach ($urls as $url) {
    echo "Checking $url ... ";
    $headers = @get_headers($url);
    if ($headers && strpos($headers[0], '200')) {
        echo "OK\n";
    } else {
        echo "FAIL\n";
    }
}
