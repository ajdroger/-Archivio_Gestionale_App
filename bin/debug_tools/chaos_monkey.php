<?php
/**
 * Chaos Monkey Placeholder
 * 
 * Script per simulare guasti randomici in ambiente di staging.
 * NON ESEGUIRE IN PRODUZIONE.
 */

if (getenv('APP_ENV') === 'production') {
    die("Chaos Monkey cannot run in production!\n");
}

echo "Simulating random failure...\n";
// Randomly kill a connection or delete a cache key
// ...
echo "Chaos completed.\n";
