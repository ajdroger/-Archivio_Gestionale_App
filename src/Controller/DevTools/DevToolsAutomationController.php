<?php

namespace MCAG\Controller\DevTools;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Mustache_Engine;

/**
 * Controller per la Console di Automazione (v5.0)
 * 
 * Implementa la logica di navigazione persistente (cd, dir, ls)
 * e l'esecuzione di script di manutenzione.
 */
class DevToolsAutomationController
{
    private Mustache_Engine $mustache;

    public function __construct(Mustache_Engine $mustache)
    {
        $this->mustache = $mustache;
    }

    /**
     * Gestisce l'esecuzione dei comandi da console web.
     * Supporta cambio directory persistente via sessione.
     */
    public function executeCommand(Request $request, Response $response): Response
    {
        // Avvia sessione se non attiva (per persistenza CWD)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $data = $request->getParsedBody();
        $command = trim($data['command'] ?? '');

        // Inizializza CWD (Current Working Directory)
        if (!isset($_SESSION['cwd'])) {
            $_SESSION['cwd'] = realpath(__DIR__ . '/../../../'); // Project Root
        }

        $currentDir = $_SESSION['cwd'];
        $output = '';

        // --- Logica Comandi Custom ---

        // 1. Cambio Directory (cd)
        if (str_starts_with($command, 'cd ')) {
            $path = trim(substr($command, 3));

            // Risolvi percorso relativo
            $newPath = realpath($currentDir . DIRECTORY_SEPARATOR . $path);

            if ($newPath && is_dir($newPath)) {
                // Security Check: Previeni uscita dal progetto (opzionale, ma raccomandato)
                $_SESSION['cwd'] = $newPath;
                $output = "Directory changed to: " . $newPath;
            } else {
                $output = "Error: Directory not found.";
            }
        }
        // 2. Clear Console
        elseif ($command === 'clear' || $command === 'cls') {
            $output = "Console cleared.";
        }
        // 3. Esecuzione Shell (ls, dir, php, etc.)
        else {
            // Whitelist di comandi permessi
            $allowed = ['ls', 'dir', 'php', 'whoami', 'git'];
            $cmdBase = explode(' ', $command)[0];

            if (in_array($cmdBase, $allowed)) {
                $cwd = getcwd();
                chdir($currentDir); // Spostati nella CWD della sessione

                try {
                    $output = shell_exec($command . " 2>&1");
                } catch (\Throwable $e) {
                    $output = "Execution Error: " . $e->getMessage();
                } finally {
                    chdir($cwd); // Ripristina
                }
            } else {
                $output = "Command not allowed in Automation Console.";
            }
        }

        $response->getBody()->write(json_encode([
            'output' => $output,
            'cwd' => $_SESSION['cwd']
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
