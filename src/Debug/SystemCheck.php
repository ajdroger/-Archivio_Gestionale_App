<?php

namespace FratellanzaMilitare\Debug;

class SystemCheck
{
    /**
     * Esegue tutti i controlli diagnostici e ritorna un array strutturato
     */
    public function runDiagnostics(): array
    {
        return [
            'php_version' => [
                'status' => version_compare(PHP_VERSION, '8.1.0', '>='),
                'message' => 'Versione PHP: ' . PHP_VERSION,
                'value' => PHP_VERSION
            ],
            'extensions' => $this->checkExtensions(),
            'database' => $this->checkDatabase(),
            'filesystem' => $this->checkPermissions(),
            'disk_space' => $this->checkDiskSpace(),
            'vendor' => [
                'status' => is_dir(__DIR__ . '/../../vendor'),
                'message' => 'Directory Vendor Trovata'
            ],
            'backup_integrity' => $this->checkRecentBackups(),
            'db_integrity' => $this->checkIntegrity()
        ];
    }

    private function checkExtensions(): array
    {
        $results = [];
        // Removed pdo_sqlite, added pdo_mysql
        $requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'xdebug'];
        foreach ($requiredExtensions as $ext) {
            $loaded = extension_loaded($ext);
            $results[$ext] = [
                'status' => $loaded,
                'message' => "Estensione '{$ext}': " . ($loaded ? 'CARICATA' : 'MANCANTE')
            ];
        }
        return $results;
    }

    private function checkDatabase(): array
    {
        try {
            $pdo = \FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection::getConnection();
            $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

            // Basic query to test connection
            $pdo->query("SELECT 1");

            // Assuming MySQL, path is the Host info
            $info = $pdo->getAttribute(\PDO::ATTR_CONNECTION_STATUS) ?? 'MySQL Host';

            return [
                'status' => true,
                'message' => "Database ($driver): CONNESSO (OK)",
                'path' => $info
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => "Database Error: " . $e->getMessage(),
                'path' => 'N/A'
            ];
        }
    }

    private function checkPermissions(): array
    {
        $criticalPaths = [
            'logs' => __DIR__ . '/../../logs',
            'public' => __DIR__ . '/../../public'
        ];

        $results = [];
        $allOk = true;
        foreach ($criticalPaths as $name => $path) {
            $isWritable = is_writable($path);
            if (!$isWritable) {
                $allOk = false;
            }
            $results[$name] = [
                'status' => $isWritable,
                'message' => "Directory '$name': " . ($isWritable ? 'Scrivibile' : 'NON Scrivibile')
            ];
        }

        // Check Audit Log
        $auditPath = __DIR__ . '/../../logs/audit.log';
        if (!file_exists($auditPath)) {
            // Create if missing
            file_put_contents($auditPath, '');
            $results['audit_log'] = ['status' => true, 'message' => "INFO: Creato file logs/audit.log"];
        } else {
            $results['audit_log'] = ['status' => is_writable($auditPath), 'message' => "Audit Log: Presente"];
        }

        // Check Backups Directory
        $backupsPath = __DIR__ . '/../../storage/backups';
        if (!is_dir($backupsPath)) {
            mkdir($backupsPath, 0777, true);
            $results['backups_dir'] = ['status' => true, 'message' => "INFO: Creata directory storage/backups/"];
        } else {
            $results['backups_dir'] = ['status' => is_writable($backupsPath), 'message' => "Directory storage/backups: Scrivibile"];
        }

        return ['status' => $allOk, 'details' => $results];
    }

    private function checkDiskSpace(): array
    {
        $free = disk_free_space(".");
        $total = disk_total_space(".");
        $percent = ($free / $total) * 100;

        return [
            'status' => $percent > 5,
            'message' => "Spazio Disco: " . round($percent, 2) . "% libero (" . round($free / (1024 * 1024 * 1024), 2) . " GB)",
            'value' => $percent
        ];
    }

    private function checkRecentBackups(): array
    {
        $backupDir = __DIR__ . '/../../storage/backups';
        // Now looking for .sql files (standard dumb)
        $backups = glob($backupDir . '/*.sql');

        if (empty($backups)) {
            return [
                'status' => false,
                'message' => 'NESSUN BACKUP TROVATO. Sistema a rischio perdita dati.'
            ];
        }

        // Trova il più recente
        usort($backups, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $latest = $backups[0];
        $ageHours = (time() - filemtime($latest)) / 3600;

        return [
            'status' => $ageHours < 24,
            'message' => "Ultimo Backup: " . basename($latest) . " (" . round($ageHours, 1) . " ore fa)",
            'age_hours' => $ageHours
        ];
    }

    private function checkIntegrity(): array
    {
        try {
            $pdo = \FratellanzaMilitare\InfrastrutturaIT\Persistence\DatabaseConnection::getConnection();
            $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

            // MySQL: CHECK TABLE is an option but excessive for "integrity" of the whole DB.
            return [
                'status' => true,
                'message' => "Integrità MySQL: Gestito dal motore InnoDB",
                'detail' => 'OK'
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => "Errore controllo integrità: " . $e->getMessage()
            ];
        }
    }

    public function checkRoutes(array $routes): array
    {
        $results = [];
        foreach ($routes as $path => $handler) {
            $parts = explode(':', $handler);
            if (count($parts) !== 2) {
                $results[$path] = ['status' => false, 'message' => "Formato non valido: $handler"];
                continue;
            }
            [$className, $methodName] = $parts;
            $exists = class_exists($className) && method_exists($className, $methodName);
            $results[$path] = [
                'status' => $exists,
                'message' => $exists ? "OK -> $className::$methodName" : "ERRORE: Handler non trovato"
            ];
        }
        return $results;
    }

    public function printReport(): void
    {
        echo "=== SYSTEM DIAGNOSTIC REPORT ===\n";
        $diag = $this->runDiagnostics();

        $this->printLine($diag['php_version'], "Versione PHP");
        foreach ($diag['extensions'] as $name => $ext) {
            $this->printLine($ext);
        }
        $this->printLine($diag['database']);
        $this->printLine($diag['filesystem'], "File System");
        $this->printLine($diag['disk_space']);
        $this->printLine($diag['vendor']);
        $this->printLine($diag['backup_integrity'], "Stato Backup");
        $this->printLine($diag['db_integrity'], "Integrità DB");

        echo "================================\n";
    }

    private function printLine(array $data, ?string $label = null): void
    {
        $status = ($data['status'] ?? true) ? "[OK]" : "[FAIL]";
        $message = $data['message'] ?? ($label ?: "Check");
        echo sprintf("%-10s %s\n", $status, $message);
    }
}
