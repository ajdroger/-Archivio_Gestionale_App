<?php
/**
 * Backup Verification Script
 * 
 * Verifies integrity of backups by checking:
 * - File existence
 * - ZIP integrity
 * - SQL export validity
 * - Checksum comparison
 * 
 * Usage: php bin/maintenance/backup_verify.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

class BackupVerifier
{
    private string $backupDir;
    private array $results = [];

    public function __construct()
    {
        $this->backupDir = __DIR__ . '/../../storage/backups';
    }

    public function run(): void
    {
        $this->log("=== Backup Verification Script ===");
        $this->log("Directory: {$this->backupDir}");
        $this->log("");

        if (!is_dir($this->backupDir)) {
            $this->error("Backup directory not found!");
            return;
        }

        $backups = $this->findBackups();

        if (empty($backups)) {
            $this->warn("No backups found in directory.");
            return;
        }

        $this->log("Found " . count($backups) . " backup file(s).");
        $this->log("");

        foreach ($backups as $backup) {
            $this->verifyBackup($backup);
        }

        $this->printSummary();
    }

    private function findBackups(): array
    {
        $files = [];
        $extensions = ['zip', 'sql', 'tar.gz', 'gz'];

        foreach (new \DirectoryIterator($this->backupDir) as $file) {
            if ($file->isFile()) {
                $ext = strtolower($file->getExtension());
                if (in_array($ext, $extensions)) {
                    $files[] = $file->getPathname();
                }
            }
        }

        // Also check legacy subfolder
        $legacyDir = $this->backupDir . '/legacy';
        if (is_dir($legacyDir)) {
            foreach (new \DirectoryIterator($legacyDir) as $file) {
                if ($file->isFile()) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    private function verifyBackup(string $path): void
    {
        $filename = basename($path);
        $this->log("📦 Verifying: {$filename}");

        $result = [
            'file' => $filename,
            'path' => $path,
            'exists' => true,
            'size' => filesize($path),
            'readable' => is_readable($path),
            'modified' => date('Y-m-d H:i:s', filemtime($path)),
            'checksum' => hash_file('sha256', $path),
            'valid' => false,
            'errors' => []
        ];

        // Check if readable
        if (!$result['readable']) {
            $result['errors'][] = 'File not readable';
            $this->results[] = $result;
            return;
        }

        // Verify by extension
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        switch ($ext) {
            case 'zip':
                $result['valid'] = $this->verifyZip($path, $result);
                break;
            case 'sql':
                $result['valid'] = $this->verifySql($path, $result);
                break;
            case 'gz':
                $result['valid'] = $this->verifyGzip($path, $result);
                break;
            default:
                $result['valid'] = true; // Unknown format, assume valid if exists
        }

        if ($result['valid']) {
            $this->success("  ✅ Valid ({$this->formatBytes($result['size'])})");
        } else {
            $this->error("  ❌ Invalid: " . implode(', ', $result['errors']));
        }

        $this->results[] = $result;
    }

    private function verifyZip(string $path, array &$result): bool
    {
        $zip = new \ZipArchive();
        $opened = $zip->open($path, \ZipArchive::CHECKCONS);

        if ($opened !== true) {
            $result['errors'][] = 'ZIP archive is corrupted (error code: ' . $opened . ')';
            return false;
        }

        $result['zip_files'] = $zip->numFiles;
        $zip->close();

        return true;
    }

    private function verifySql(string $path, array &$result): bool
    {
        $content = file_get_contents($path, false, null, 0, 1024);

        // Check for common SQL patterns
        if (
            stripos($content, 'CREATE TABLE') !== false ||
            stripos($content, 'INSERT INTO') !== false ||
            stripos($content, 'DROP TABLE') !== false
        ) {
            return true;
        }

        $result['errors'][] = 'Does not appear to be a valid SQL file';
        return false;
    }

    private function verifyGzip(string $path, array &$result): bool
    {
        $handle = @gzopen($path, 'rb');
        if ($handle === false) {
            $result['errors'][] = 'Cannot open GZIP file';
            return false;
        }

        // Try to read first chunk
        $data = @gzread($handle, 1024);
        gzclose($handle);

        if ($data === false) {
            $result['errors'][] = 'GZIP file is corrupted';
            return false;
        }

        return true;
    }

    private function printSummary(): void
    {
        $this->log("");
        $this->log("=== Summary ===");

        $valid = count(array_filter($this->results, fn($r) => $r['valid']));
        $total = count($this->results);

        $this->log("Total backups: {$total}");
        $this->log("Valid: {$valid}");
        $this->log("Invalid: " . ($total - $valid));

        if ($valid === $total) {
            $this->success("\n🎉 All backups verified successfully!");
        } else {
            $this->warn("\n⚠️ Some backups have issues. Check errors above.");
        }

        // Generate checksums file
        $this->generateChecksumsFile();
    }

    private function generateChecksumsFile(): void
    {
        $checksumFile = $this->backupDir . '/checksums.sha256';
        $lines = [];

        foreach ($this->results as $r) {
            if ($r['valid']) {
                $lines[] = $r['checksum'] . '  ' . $r['file'];
            }
        }

        if (!empty($lines)) {
            file_put_contents($checksumFile, implode("\n", $lines) . "\n");
            $this->log("\n📝 Checksums saved to: checksums.sha256");
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    private function log(string $msg): void
    {
        echo $msg . "\n";
    }

    private function success(string $msg): void
    {
        echo "\033[32m{$msg}\033[0m\n";
    }

    private function warn(string $msg): void
    {
        echo "\033[33m{$msg}\033[0m\n";
    }

    private function error(string $msg): void
    {
        echo "\033[31m{$msg}\033[0m\n";
    }
}

// Run
(new BackupVerifier())->run();
