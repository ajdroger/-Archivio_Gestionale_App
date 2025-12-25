# Configuration
$sourceDb = "../database.sqlite"
$backupDir = "../backups"
$retentionDays = 7
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$dbName = "database.sqlite"

# Ensure backup directory exists
$backupPath = Join-Path $PSScriptRoot $backupDir
if (!(Test-Path $backupPath)) {
    New-Item -ItemType Directory -Force -Path $backupPath | Out-Null
    Write-Host "Created backup directory: $backupPath"
}

# Source path
$sourcePath = Join-Path $PSScriptRoot $sourceDb

if (Test-Path $sourcePath) {
    # Destination path
    $destFile = "$dbName.$timestamp.bak"
    $destPath = Join-Path $backupPath $destFile

    # Perform Backup (Copy)
    Copy-Item -Path $sourcePath -Destination $destPath
    Write-Host "Backup created: $destFile"

    # Retention Policy: Remove backups older than X days
    $cutoffDate = (Get-Date).AddDays(-$retentionDays)
    Get-ChildItem -Path $backupPath -Filter "$dbName.*.bak" | Where-Object { $_.CreationTime -lt $cutoffDate } | ForEach-Object {
        Remove-Item $_.FullName
        Write-Host "Deleted old backup: $($_.Name)"
    }
} else {
    Write-Error "Database file not found at: $sourcePath"
    exit 1
}
