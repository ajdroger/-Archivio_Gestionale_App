<?php

namespace FratellanzaMilitare\Controller\DevTools;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use FratellanzaMilitare\Debug\LogAnalyzer;

/**
 * DevTools Script Controller
 * 
 * Handles script execution, renamer tool, and log tracing
 */
class DevToolsScriptController
{
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
        } else {
            $output = "Tipo di file non supportato.";
        }

        $response->getBody()->write(json_encode(['output' => $output ?: 'Nessun output o errore.']));
        return $response->withHeader('Content-Type', 'application/json');
    }

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

    public function logTrace(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $requestId = $data['requestId'] ?? '';

        if (empty($requestId)) {
            $response->getBody()->write(json_encode(['error' => 'ID richiesto.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $analyzer = new LogAnalyzer(__DIR__ . '/../../../logs/app.log');
        $logs = $analyzer->getLogsByRequestId($requestId);

        $response->getBody()->write(json_encode(['logs' => $logs]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
