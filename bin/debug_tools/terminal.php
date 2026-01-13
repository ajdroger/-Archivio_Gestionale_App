<?php
/**
 * Simple Terminal Backend for DevTools
 * Executes commands and returns output.
 * WARNING: UNAUTHENTICATED RCE. ONLY FOR LOCAL DEBUGGING/DEV USE.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../vendor/autoload.php';

// Security Check: Only allow in Dev/Local
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

$env = $_ENV['APP_ENV'] ?? 'production';
if ($env === 'production') {
    http_response_code(403);
    echo json_encode(['output' => 'Terminal disabled in production.']);
    exit;
}

// Get Input
$input = json_decode(file_get_contents('php://input'), true);
$cmd = $input['cmd'] ?? '';
$cmd = trim($cmd);

if (empty($cmd)) {
    echo json_encode(['output' => '']);
    exit;
}

// Special Commands
if ($cmd === 'clear' || $cmd === 'cls') {
    echo json_encode(['output' => '__CLEAR__']);
    exit;
}

// Execute
// Redirect stderr to stdout to capture errors
$output = [];
$returnVar = 0;
// Note: windows pwsh or cmd handling might vary. 
// Just using exec for basic commands.
exec("$cmd 2>&1", $output, $returnVar);

$outputText = implode("\n", $output);

echo json_encode(['output' => $outputText]);

