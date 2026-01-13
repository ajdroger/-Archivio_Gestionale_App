<?php

namespace FratellanzaMilitare\Controller\DevTools;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use FratellanzaMilitare\Debug\LogAnalyzer;

/**
 * DevTools Script Controller
 * 
 * Handles script execution, renamer tool, log tracing, and terminal commands
 */
/**
 * Controller per l'esecuzione di script di manutenzione e debug.
 * 
 * Gestisce l'esecuzione sicura di script PHP e PowerShell, il tool di rinomina progetto,
 * il tracciamento dei log e il terminale interattivo.
 */
class DevToolsScriptController
{
    /**
     * Esegue uno script presente nella directory bin/ o tests/.
     * 
     * Implementa controlli di sicurezza per evitare directory traversal.
     * Supporta script .php (eseguiti via php/pest) e .ps1 (via powershell).
     * Registra l'output in un log dedicato.
     * 
     * @param Request $request
     * @param Response $response
     * @return Response JSON contenente l'output
     */
    public function runScript(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $scriptPath = $data['script'] ?? '';

        // Security: Block directory traversal and restrict to allowed folders
        $baseDir = realpath(__DIR__ . '/../../../');
        $realPath = realpath($baseDir . '/' . $scriptPath);

        if (!$realPath || !str_starts_with($realPath, $baseDir)) {
            $response->getBody()->write(json_encode(['output' => 'Errore: Percorso non consentito.']));
            return $response->withHeader('Content-Type', 'application/json');
        }

        $command = '';
        if (str_ends_with($realPath, '.php')) {
            if (str_contains($realPath, 'tests')) {
                $command = 'php vendor/bin/pest --configuration phpunit.xml "' . $realPath . '" --colors=never';
            } else {
                $command = 'php "' . $realPath . '"';
            }
        } elseif (str_ends_with($realPath, '.ps1')) {
            $command = 'powershell -ExecutionPolicy Bypass -File "' . $realPath . '"';
        }

        if ($command) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            $oldCwd = getcwd();
            chdir($baseDir);
            $output = shell_exec($command . ' 2>&1');
            chdir($oldCwd);

            // LOGGING: Save execution result to dedicated folder instead of root
            $logDir = $baseDir . '/var/logs/debug';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0777, true);
            }
            $executionLog = $logDir . '/script_executions.log';
            $timestamp = date('Y-m-d H:i:s');
            $logEntry = "[$timestamp] SCRIPT: $scriptPath\nCOMMAND: $command\nOUTPUT:\n$output\n" . str_repeat('-', 40) . "\n";
            file_put_contents($executionLog, $logEntry, FILE_APPEND);
        } else {
            $output = "Tipo di file non supportato.";
        }

        $response->getBody()->write(json_encode(['output' => $output ?: 'Nessun output o errore.']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Avvia il tool di rinomina del progetto (SystemRenamer).
     * 
     * @param Request $request
     * @param Response $response
     * @return Response JSON
     */
    public function runRenamer(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $newName = $data['new_name'] ?? '';
        $isDryRun = ($data['dry_run'] ?? '0') === '1';

        if (empty($newName)) {
            $response->getBody()->write(json_encode(['output' => 'Errore: Nome progetto mancante.']));
            return $response->withHeader('Content-Type', 'application/json');
        }

        if (!preg_match('/^[a-z0-9-]+$/', $newName)) {
            $response->getBody()->write(json_encode(['output' => 'Errore: Il nome deve essere in formato slug (solo lettere minuscole, numeri e trattini).']));
            return $response->withHeader('Content-Type', 'application/json');
        }

        $toolPath = __DIR__ . '/../../../tests/RenamerTool/SystemRenamer.php';
        if (!file_exists($toolPath)) {
            $response->getBody()->write(json_encode(['output' => 'Errore: Tool SystemRenamer non trovato sui server.']));
            return $response->withHeader('Content-Type', 'application/json');
        }

        require_once $toolPath;

        ob_start();
        try {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            $renamer = new \SystemRenamer($newName, $isDryRun);
            $renamer->execute();

        } catch (\Throwable $e) {
            echo "\n[EXCEPTION] " . $e->getMessage();
        }
        $output = ob_get_clean();

        $response->getBody()->write(json_encode(['output' => $output]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Recupera i log specifici per una Request ID (Trace Explorer).
     * 
     * @param Request $request
     * @param Response $response
     * @return Response JSON
     */
    public function logTrace(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $requestId = $data['requestId'] ?? '';

        if (empty($requestId)) {
            $response->getBody()->write(json_encode(['error' => 'ID richiesto.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $analyzer = new LogAnalyzer(__DIR__ . '/../../../var/logs/app.log');
        $logs = $analyzer->getLogsByRequestId($requestId);

        $response->getBody()->write(json_encode(['logs' => $logs]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Endpoint per il terminale interattivo (Web Shell).
     * 
     * Esegue comandi shell arbitrari mantenendo la directory di lavoro in sessione.
     * ATTENZIONE: Questo è un endpoint estremamente sensibile.
     * 
     * @param Request $request
     * @param Response $response
     * @return Response JSON output del comando
     */
    public function terminal(Request $request, Response $response): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['term_cwd'])) {
            $_SESSION['term_cwd'] = realpath(__DIR__ . '/../../../');
        }

        $input = $request->getParsedBody();
        $cmd = $input['cmd'] ?? '';

        if (empty($cmd)) {
            $response->getBody()->write(json_encode(['output' => '', 'cwd' => $_SESSION['term_cwd']]));
            return $response->withHeader('Content-Type', 'application/json');
        }

        $cwd = $_SESSION['term_cwd'];
        $output = '';

        if (preg_match('/^cd\s+(.+)$/', $cmd, $matches)) {
            $target = trim($matches[1]);

            if ($target === '..') {
                $newDir = dirname($cwd);
            } elseif (str_starts_with($target, '/') || str_starts_with($target, '\\') || preg_match('/^[a-zA-Z]:/', $target)) {
                $newDir = $target;
            } else {
                $newDir = $cwd . DIRECTORY_SEPARATOR . $target;
            }

            if (is_dir($newDir)) {
                $_SESSION['term_cwd'] = realpath($newDir);
            } else {
                $output = "Impossibile trovare il percorso: $target";
            }
        } elseif (strtolower($cmd) === 'cls' || strtolower($cmd) === 'clear') {
            $output = '__CLEAR__';
        } else {
            // WINDOWS SUPPORT: Force PowerShell for "Bash-like" experience (ls, cat, etc.)
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Remove dangerous chars for safety if needed, but this is a Pro tool.
                // Escape quotes for PowerShell
                $safeCmd = str_replace('"', '\"', $cmd);
                $psCommand = "powershell -NoProfile -NonInteractive -Command \"Set-Location '$cwd'; $cmd\"";
                $output = shell_exec($psCommand . ' 2>&1');

                // Decode output if needed (PowerShell output encoding)
                // Often CP850 or UTF-16LE. Shell_exec usually returns string.
                // We'll trust PHP handles the stream somewhat, or fix encoding if mojibake appears.
            } else {
                // Linux/Mac
                $fullCmd = 'cd "' . $cwd . '" && ' . $cmd . ' 2>&1';
                $output = shell_exec($fullCmd);
            }
        }

        $response->getBody()->write(json_encode([
            'output' => $output,
            'cwd' => $_SESSION['term_cwd']
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
