<?php

require __DIR__ . '/../../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Psr\Http\Message\ServerRequestInterface;

/**
 * SystemRenamer & RouteIntegrityTester
 *
 * Usage: php tests/RenamerTool/SystemRenamer.php "nuovo-nome-cartella" [dry-run]
 */

class SystemRenamer
{
    private string $rootDir;
    private string $currentName;
    private string $newName;
    private string $logFile;
    private bool $dryRun;


    public function __construct(string $newName, bool $dryRun = false)
    {
        // Adjusted for being in tests/RenamerTool/
        $this->rootDir = realpath(__DIR__ . '/../../');
        $this->currentName = basename($this->rootDir);
        $this->newName = $newName;
        $this->dryRun = $dryRun;
        $this->logFile = $this->rootDir . '/logs/system_rename_audit_' . date('Y-m-d_H-i-s') . '.log';

        // Ensure log dir exists
        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0777, true);
        }
    }

    public function execute(): void
    {
        $this->log("=== SYSTEM RENAMER STARTED ===");
        $this->log("Root: " . $this->rootDir);
        $this->log("Current Name: " . $this->currentName);
        $this->log("Target Name: " . $this->newName);
        $this->log("Mode: " . ($this->dryRun ? "DRY-RUN" : "EXECUTE"));

        if ($this->currentName === $this->newName) {
            $this->log("Error: Target name is same as current.");
            echo "Error: Target name is same as current.\n";
            return;
        }

        // 1. Refactor Content
        $this->refactorContent();

        // 2. Verify Routes (Simulated)
        $this->verifyRoutesInternal();

        // 3. Rename Directory Instructions / Action
        if (!$this->dryRun) {
            $this->log("Content Updated. To complete the operation, the root directory must be renamed.");
            $this->handleDirectoryRename();
        }

        $this->log("=== SYSTEM RENAMER COMPLETED ===");
        echo "Operation Completed. Check log: " . $this->logFile . "\n";
    }

    private function refactorContent(): void
    {
        $dirs = ['src', 'templates', 'public', 'config', 'tests'];
        $count = 0;

        foreach ($dirs as $dir) {
            $path = $this->rootDir . '/' . $dir;
            if (!is_dir($path)) {
                continue;
            }

            $this->log("Scanning directory: " . $dir);

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    continue;
                }
                if (!in_array($file->getExtension(), ['php', 'mustache', 'js', 'css', 'json', 'md', 'bat'])) {
                    continue;
                }
                // Exclude self and logs if scanned
                if (strpos($file->getPathname(), 'SystemRenamer.php') !== false) {
                    continue;
                }

                $content = file_get_contents($file->getPathname());
                // Simple string replacement: /old-name/ -> /new-name/
                $search = '/' . $this->currentName . '/';
                $replace = '/' . $this->newName . '/';

                if (str_contains($content, $search)) {
                    $this->log("Found reference in: " . $file->getPathname());

                    if (!$this->dryRun) {
                        $newContent = str_replace($search, $replace, $content);
                        file_put_contents($file->getPathname(), $newContent);
                        $this->log("-> FIXED: Updated reference.");
                        $count++;
                    }
                }
            }
        }
        $this->log("Total files updated: $count");
    }

    private function verifyRoutesInternal(): void
    {
        $this->log("--- Route Integrity Check ---");

        try {
            // Include bootstrap but prevent running the app immediately if possible
            $app = AppFactory::create();

            // Syntax check via lint
            $this->log("Performing syntax check on critical files...");
            $filesToCheck = [
                '/src/routes.php',
                '/src/Controller/LoginController.php',
                '/templates/layout.mustache'
            ];

            foreach ($filesToCheck as $f) {
                $fullPath = $this->rootDir . $f;
                if (file_exists($fullPath)) {
                    $output = [];
                    $returnVar = 0;
                    exec("php -l \"$fullPath\"", $output, $returnVar);
                    if ($returnVar === 0) {
                        $this->log("Syntax OK: $f");
                    } else {
                        $this->log("SYNTAX ERROR: $f - " . implode(" ", $output));
                    }
                }
            }

        } catch (\Exception $e) {
            $this->log("Route verification exception: " . $e->getMessage());
        }
    }

    private function handleDirectoryRename(): void
    {
        // Renaming the root dir from inside a PHP script running in it prone to locking.
        // We will generate a Batch script to do it.
        $batchFile = $this->rootDir . '/rename_finalize.bat';
        $parentDir = dirname($this->rootDir);
        $oldName = $this->currentName;
        $newName = $this->newName;

        $batchContent = "@echo off\r\n";
        $batchContent .= "echo Finalizing System Rename...\r\n";
        $batchContent .= "timeout /t 2 /nobreak > nul\r\n";
        $batchContent .= "cd /d \"$parentDir\"\r\n";
        $batchContent .= "if exist \"$oldName\" ren \"$oldName\" \"$newName\"\r\n";
        $batchContent .= "echo Done. Directory renamed from $oldName to $newName.\r\n";
        $batchContent .= "pause\r\n";

        file_put_contents($batchFile, $batchContent);

        $this->log("WARNING: Cannot rename root directory while script is running inside it.");
        $this->log("ACTION REQUIRED: Run the generated script 'rename_finalize.bat' located in root to finish.");
        echo "\n[IMPORTANT] Created 'rename_finalize.bat'. Run this file to physical rename the folder.\n";
    }

    private function log(string $message): void
    {
        $line = "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
        file_put_contents($this->logFile, $line, FILE_APPEND);
    }
}

// CLI Entry Point
if (php_sapi_name() === 'cli') {
    if ($argc < 2) {
        echo "Usage: php tests/RenamerTool/SystemRenamer.php \"new_folder_name\"\n";
        exit(1);
    }

    $dryRun = in_array('--dry-run', $argv);
    $renamer = new SystemRenamer($argv[1], $dryRun);
    $renamer->execute();
}

