<?php
/**
 * Simple Terminal Backend for DevTools
 * Executes commands and returns output.
 * WARNING: UNAUTHENTICATED RCE. ONLY FOR LOCAL DEBUGGING/DEV USE.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../vendor/autoload.php';

ob_start(); // Start capturing ANY output (warnings, echoes, etc.)

try {
    // Security Check: Only allow in Dev/Local
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->safeLoad();

    $env = $_ENV['APP_ENV'] ?? 'production';
    if ($env === 'production') {
        throw new Exception('Terminal disabled in production.');
    }

    // Get Input
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    if (!is_array($input)) {
        // Fallback if decode fails (maybe empty or bad encoding)
        $input = [];
    }

    $cmd = $input['cmd'] ?? '';
    $cmd = trim($cmd);

    if (empty($cmd)) {
        ob_end_clean();
        echo json_encode(['output' => '']);
        exit;
    }

    // Special Commands
    if ($cmd === 'clear' || $cmd === 'cls') {
        ob_end_clean();
        echo json_encode(['output' => '__CLEAR__']);
        exit;
    }

    // Execute
    // Redirect stderr to stdout to capture errors
    $output = [];
    $returnVar = 0;

    // Attempt execution
    exec("$cmd 2>&1", $output, $returnVar);

    $outputText = implode("\n", $output);

    // Clear buffer before sending JSON
    $bufferedOutput = ob_get_contents();
    ob_end_clean();

    // If there was spurious output, maybe append it to outputText (debugging) or ignore it
    // For now, we prefer to ignore it to preserve JSON structure, or log it.
    if (!empty($bufferedOutput) && (!isset($outputText) || $outputText === '')) {
        $outputText .= "\n[System Buffer]: " . $bufferedOutput;
    }

    echo json_encode(['output' => $outputText]);

} catch (Exception $e) {
    ob_end_clean(); // Ensure no partial output
    http_response_code(500);
    echo json_encode(['output' => 'Error: ' . $e->getMessage()]);
}

