<?php
$envFile = __DIR__ . '/../.env';
$content = file_get_contents($envFile);
$lines = explode("\n", $content);
$newLines = [];

foreach ($lines as $line) {
    $trim = trim($line);
    if (empty($trim))
        continue;

    // Keep comments
    if (str_starts_with($trim, '#')) {
        $newLines[] = $trim;
        continue;
    }

    // Keep valid KEY=VALUE pairs, excluding TOTP
    if (str_contains($trim, '=')) {
        [$key, $val] = explode('=', $trim, 2);
        if (trim($key) === 'TOTP_ENCRYPTION_KEY')
            continue;
        $newLines[] = $trim;
    }
}

// Add clean key
$key = 'def000002a4ee6a71005c034b157c7b1d59b9086547a07a378ddb643be1f452a92847ac09cdf247e2aae7610ec354d81aa3e062dd90613355b21610790a6c9d44';
$newLines[] = "TOTP_ENCRYPTION_KEY=$key"; // No quotes to be safe

file_put_contents($envFile, implode("\n", $newLines) . "\n");
echo "Refreshed .env with clean key.\n";
