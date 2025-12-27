# Script di Deploy Automatico - Enterprise v2.1
# Autore: Soobadur Mohammad Ajmeer
# Data Aggiornamento: 27/12/2025

Write-Host "🚀 Avvio procedura di Deploy Enterprise" -ForegroundColor Green
$ErrorActionPreference = "Stop"

# 1. Pre-Flight Checks
Write-Host "`n🩺 Esecuzione Health Check..." -ForegroundColor Cyan
php bin/tools/health_check.php
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Health Check Fallito. Correggi gli errori prima del deploy." -ForegroundColor Red
    exit 1
}

# 2. Test Suite
Write-Host "`n🧪 Esecuzione Test Suite (Pest)..." -ForegroundColor Cyan
vendor\bin\pest -c config\phpunit.xml --no-coverage 2>&1 | Tee-Object -FilePath "logs/tests/deploy_pest.txt"
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Test Suite Fallita. Impossibile procedere." -ForegroundColor Red
    exit 1
}

# 3. Verifica GitHub CLI
if (-not (Get-Command "gh" -ErrorAction SilentlyContinue)) {
    Write-Host "⚠️  GitHub CLI (gh) non trovato." -ForegroundColor Yellow
    exit 1
}

# 4. Git Push
Write-Host "`n⬆️  Sincronizzazione GitHub..." -ForegroundColor Cyan
git add .
git commit -m "Deploy: Automated Update $(Get-Date -Format 'yyyy-MM-dd HH:mm')"
git push origin master

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Sincronizzazione Completata!" -ForegroundColor Green
}
else {
    Write-Host "⚠️  Push completato con warning o nulla da fare." -ForegroundColor Yellow
}

Read-Host "Premere Invio per uscire..."
