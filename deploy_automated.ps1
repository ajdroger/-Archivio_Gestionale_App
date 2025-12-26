# Script di Deploy Automatico su GitHub e Vercel
# Autore: Soobadur Mohammad Ajmeer
# Data: 25/12/2025

Write-Host "🚀 Avvio procedura di Deploy per utente: ajdroger" -ForegroundColor Green

# 1. Verifica GitHub CLI
if (-not (Get-Command "gh" -ErrorAction SilentlyContinue)) {
    Write-Host "⚠️  GitHub CLI (gh) non trovato." -ForegroundColor Yellow
    Write-Host "Per favore installalo da: https://cli.github.com/"
    Write-Host "Una volta installato, riavvia questo script."
    exit
}

# 2. Login Check
Write-Host "🔑 Verifica login GitHub..."
gh auth status
if ($LASTEXITCODE -ne 0) {
    Write-Host "⚠️  Non sei loggato. Avvio login browser..." -ForegroundColor Yellow
    Write-Host "❗ IMPORTANTE: Completa il login nel browser e usa il tuo Google Authenticator."
    gh auth login -p https -w
}

# 3. Creazione Repository
Write-Host "📦 Creazione repository privata 'fratellanza-militare-archivio'..."
gh repo create fratellanza-militare-archivio --private --source=. --remote=origin

# 4. Push
Write-Host "⬆️  Caricamento codice (Push)..."
git push -u origin main

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Codice caricato su GitHub con successo!" -ForegroundColor Green
    Write-Host "🔗 URL: https://github.com/ajdroger/fratellanza-militare-archivio"
}
else {
    Write-Host "❌ Errore durante il push. Verifica che il repo non esista già." -ForegroundColor Red
}

# 5. Vercel Instructions
Write-Host "`n☁️  DEPLOY VERCEL" -ForegroundColor Cyan
Write-Host "Per completare il deploy su Vercel:"
Write-Host "1. Vai su https://vercel.com/new"
Write-Host "2. Importa il repo 'fratellanza-militare-archivio'"
Write-Host "3. Copia le variabili d'ambiente dal file .env (eccetto DB locale)"
Write-Host "4. Clicca Deploy."

Read-Host "Premere Invio per uscire..."
