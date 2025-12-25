# Script di verifica completa del sistema
Write-Host "=== AVVIO CHECK DI MANUTENZIONE TOTALE ===" -ForegroundColor Cyan

# 1. Esecuzione Test Unitari e Integrazione
Write-Host "`n[1/3] Esecuzione Unit & Integration Tests..." -ForegroundColor Yellow
php vendor/bin/pest --colors=always

if ($LASTEXITCODE -ne 0) {
    Write-Host "ATTENZIONE: Alcuni test sono falliti!" -ForegroundColor Red
}

# 2. Diagnostica e Analisi Database
Write-Host "`n[2/2] Esecuzione Diagnostica e Analisi Database..." -ForegroundColor Yellow
php bin/diagnostics_runner.php

Write-Host "`n=== VERIFICA COMPLETATA ===" -ForegroundColor Cyan
