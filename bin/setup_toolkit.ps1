# Hyper Grid "Surgical" Installer v1.0
$ErrorActionPreference = "Stop"
$toolsDir = Join-Path $PSScriptRoot "tools"

Write-Host ">>> INITIALIZING SURGICAL INSTALLATION PROTOCOL..." -ForegroundColor Cyan

# 1. Create Tools Directory
if (-not (Test-Path $toolsDir)) {
    New-Item -ItemType Directory -Path $toolsDir | Out-Null
    Write-Host "[+] Created local tools directory: $toolsDir" -ForegroundColor Green
}

# 2. Chocolatey (System Level Tools)
# We use 'choco upgrade' which installs if missing, upgrades if present
$chocoPackages = @("nmap", "wireshark", "john", "hashcat", "putty", "wget", "curl", "bind-toolsonly", "openjdk")

Write-Host "`n>>> EXE: DEPLOYING CORE BINARIES (CHOCOLATEY)..." -ForegroundColor Yellow
foreach ($pkg in $chocoPackages) {
    Write-Host "  [*] Deploying $pkg..." -NoNewline
    try {
        # -y to confirm all, --no-progress to reduce noise
        choco upgrade $pkg -y --no-progress | Out-Null
        Write-Host " [OK]" -ForegroundColor Green
    } catch {
        Write-Host " [FAILED] (Check Permissions?)" -ForegroundColor Red
    }
}

# 3. Python PIP (Scripting Arsenal)
$pipPackages = @("sqlmap", "shodan", "impacket", "scapy", "requests", "colorama")

Write-Host "`n>>> PY: DEPLOYING PYTHON ARSENAL (PIP)..." -ForegroundColor Yellow
foreach ($pkg in $pipPackages) {
    Write-Host "  [*] Installing $pkg..." -NoNewline
    try {
        pip install $pkg --quiet | Out-Null
        Write-Host " [OK]" -ForegroundColor Green
    } catch {
        Write-Host " [FAILED]" -ForegroundColor Red
    }
}

# 4. Git Repositories (Specialized Tools)
$gitTools = @{
    "dirsearch" = "https://github.com/maurosoria/dirsearch.git"
    "Sherlock" = "https://github.com/rasta-mouse/Sherlock.git"
    "PowerSploit" = "https://github.com/PowerShellMafia/PowerSploit.git"
    "fuzzdb" = "https://github.com/fuzzdb-project/fuzzdb.git"
}

Write-Host "`n>>> GIT: CLONING SPECIALIZED REPOSITORIES..." -ForegroundColor Yellow
foreach ($name in $gitTools.Keys) {
    $repoPath = Join-Path $toolsDir $name
    if (-not (Test-Path $repoPath)) {
        Write-Host "  [*] Cloning $name..." -NoNewline
        try {
            git clone $($gitTools[$name]) $repoPath --quiet | Out-Null
            Write-Host " [OK]" -ForegroundColor Green
        } catch {
            Write-Host " [FAILED]" -ForegroundColor Red
        }
    } else {
        Write-Host "  [*] $name already present." -ForegroundColor Gray
    }
}

Write-Host "`n>>> SURGICAL INSTALLATION COMPLETE." -ForegroundColor Cyan
Write-Host ">>> SYSTEM READY FOR ACTIVE DUTY." -ForegroundColor Cyan
