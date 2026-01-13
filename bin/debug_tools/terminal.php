<?php
/**
 * Simple Terminal Backend for DevTools
 * Executes commands and returns output.
 * WARNING: UNAUTHENTICATED RCE. ONLY FOR LOCAL DEBUGGING/DEV USE.
 */

ob_start(); // Start capturing ANY output immediately
header('Content-Type: application/json');

// Disable standard error output to prevent pollution, we capture via ob and exec
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../vendor/autoload.php';

try {
    // Security Check: Only allow in Dev/Local
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->safeLoad();

    $env = $_ENV['APP_ENV'] ?? 'production';
    if ($env === 'production') {
        throw new Exception('Terminal disabled in production.');
    }

    // Session Management for CWD
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Restore CWD
    if (isset($_SESSION['term_cwd']) && is_dir($_SESSION['term_cwd'])) {
        chdir($_SESSION['term_cwd']);
    } else {
        $_SESSION['term_cwd'] = getcwd();
    }

    // Get Input
    $inputJSON = '';
    if (php_sapi_name() === 'cli') {
        $inputJSON = file_get_contents('php://stdin');
    } else {
        $inputJSON = file_get_contents('php://input');
    }

    $input = json_decode($inputJSON, true);

    if (!is_array($input)) {
        // Fallback or debug
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

    // CD Support (Persistent)
    if (preg_match('/^cd\s+(.+)$/', $cmd, $matches)) {
        $newDir = trim($matches[1]);
        if (chdir($newDir)) {
            $_SESSION['term_cwd'] = getcwd();
            $cmd = 'echo "Directory changed to: " . Get-Location'; // Feedback for PowerShell
        } else {
            $cmd = 'echo "Failed to change directory."';
        }
    } elseif ($cmd === 'cd') {
        // Just print CWD
        $cmd = 'Get-Location';
    }

    // Execute
    // Redirect stderr to stdout to capture errors
    // On Windows, use PowerShell to support ls, pwd, dir consistent with user shell
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $cmd = 'powershell -NoProfile -Command "' . str_replace('"', '\"', $cmd) . '"';
    }

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

    // Windows Encoding Fix (CP850/CP1252 -> UTF-8)
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $outputText = mb_convert_encoding($outputText, 'UTF-8', 'CP850, Windows-1252, ISO-8859-1');
    }

    $json = json_encode(['output' => $outputText]);

    if ($json === false) {
        throw new Exception('JSON Encode Error: ' . json_last_error_msg());
    }

    echo $json;

} catch (Exception $e) {
    ob_end_clean(); // Ensure no partial output
    http_response_code(500);
    echo json_encode(['output' => 'Error: ' . $e->getMessage()]);
}
