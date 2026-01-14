#!/bin/bash

# Setup Script per MCAG Archivio (Linux)
# Version 5.4.0 Enterprise (Fluid Edition)

echo "=================================================="
echo "   MCAG ARCHIVIO - SETUP LINUX"
echo "=================================================="

# 1. Verifica Requisiti
echo "[1/5] Verifica requisiti di sistema..."
if ! command -v php &> /dev/null; then
    echo "ERRORE: PHP non trovato. Installa PHP 8.2+"
    exit 1
fi

if ! command -v composer &> /dev/null; then
    echo "ERRORE: Composer non trovato. Installalo globalmente."
    exit 1
fi

# 2. Configurazione Ambiente
echo "[2/5] Configurazione ambiente..."
if [ ! -f ../.env ]; then
    echo "Copiando .env di produzione..."
    cp env.production.example ../.env
    echo "ATTENZIONE: Modifica il file .env nella root con le tue configurazioni!"
else
    echo "File .env già presente. Skipping."
fi

# 3. Installazione Dipendenze
echo "[3/5] Installazione dipendenze backend..."
cd ..
composer install --no-dev --optimize-autoloader

if [ -f package.json ]; then
    echo "Installazione dipendenze frontend..."
    npm ci && npm run build
fi

# 4. Configurazione Database
echo "[4/5] Migrazione Database..."
# Creazione file sqlite se non esiste e driver è sqlite
touch database.sqlite
vendor/bin/phinx migrate -e production

# 5. Permessi
echo "[5/5] Impostazione permessi..."
chmod -R 775 storage logs
chown -R www-data:www-data storage logs database.sqlite

echo "=================================================="
echo "   INSTALLAZIONE COMPLETATA CON SUCCESSO!"
echo "=================================================="
echo "Ora configura il tuo Web Server (Apache/Nginx) per puntare alla cartella 'public'."
