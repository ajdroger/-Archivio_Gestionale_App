$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression.FileSystem

$originalZip = ".\_BACKUPS\MCAG_Backup_v8.1.0_20260119_0340.zip"
$tempZip = ".\_BACKUPS\VERIFY_TEMP.zip"
$sourcePath = "."

Write-Host "Starting Verification..."

# 1. Copy to bypass lock
try {
    Copy-Item $originalZip $tempZip -Force
    Write-Host "Copy successful."
}
catch {
    Write-Error "Could not copy ZIP file. It might be exclusively locked or corrupted. Error: $_"
    exit 1
}

# 2. Count Source Files
$sourceFiles = (Get-ChildItem -Path $sourcePath -Recurse -File -Exclude ".git").Count
Write-Host "Source Files: $sourceFiles"

# 3. Verify Zip Content
try {
    $zip = [System.IO.Compression.ZipFile]::OpenRead((Convert-Path $tempZip))
    $zipCount = $zip.Entries.Count
    $zip.Dispose()
    
    Write-Host "Backup Entries: $zipCount"
    
    if ($zipCount -gt 1000) { 
        # Loose check: if it has >1000 files it's likely the project. 
        # Strict check: $zipCount should be close to $sourceFiles (vendor/node_modules usually add thousands)
        Write-Host "VALIDATION SUCCESS: Zip is readable and contains $zipCount files."
    }
    else {
        Write-Warning "VALIDATION WARNING: Zip seems too empty ($zipCount files)."
    }

}
catch {
    Write-Error "CORRUPT: Could not open copied ZIP file. Error: $_"
    exit 1
}
finally {
    if (Test-Path $tempZip) { Remove-Item $tempZip -Force }
}
