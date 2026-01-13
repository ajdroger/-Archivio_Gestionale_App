<?php
/**
 * Off-Site Backup S3/Drive Uploader
 * 
 * Usage: php bin/maintenance/backup_offsite.php
 * 
 * Requires: rclone installed and configured
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$backupDir = __DIR__ . '/../../storage/backups';
$cloudDest = $_ENV['CLOUD_BACKUP_DEST'] ?? 'remote:backups/fratellanza';

echo "=== Off-Site Backup Uploader ===\n";
echo "Source: $backupDir\n";
echo "Dest:   $cloudDest\n\n";

if (!shell_exec('where rclone')) {
    echo "❌ Error: rclone not found in PATH.\n";
    echo "Please install rclone and configure it: https://rclone.org/\n";
    exit(1);
}

// Sync latest backups (last 7 days)
$cmd = "rclone copy \"$backupDir\" \"$cloudDest\" --include \"*.sql.gz\" --include \"*.zip\" --maxage 7d --progress";

echo "Executing: $cmd\n";
system($cmd, $retval);

if ($retval === 0) {
    echo "\n✅ Off-site backup completed successfully.\n";
    \MCAG\SecurityLayer\AuditTrail::getInstance()->logEvento(
        null,
        'BACKUP_OFFSITE',
        'rclone_sync_success'
    );
} else {
    echo "\n❌ Backup failed with exit code $retval.\n";
    exit(1);
}

