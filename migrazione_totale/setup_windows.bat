@echo off
setlocal EnableDelayedExpansion

title Fratellanza Militare Archivio - Setup Windows

echo ==================================================
echo    FRATELLANZA MILITARE ARCHIVIO - SETUP WINDOWS
echo ==================================================

:: 1. Verifica Requisiti
echo [1/5] Verifica requisiti di sistema...
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo ERRORE: PHP non trovato nel PATH. Installa PHP 8.2+
    pause
    exit /b 1
)

where composer >nul 2>nul
if %errorlevel% neq 0 (
    echo ERRORE: Composer non trovato.
    pause
    exit /b 1
)

:: 2. Configurazione Ambiente
echo [2/5] Configurazione ambiente...
cd ..
if not exist .env (
    echo Copiando .env di produzione...
    copy migrazione_totale\env.production.example .env
    echo ATTENZIONE: Ricordati di modificare il file .env con le tue configurazioni!
) else (
    echo File .env gia' presente. Skipping.
)

:: 3. Installazione Dipendenze
echo [3/5] Installazione dipendenze backend...
call composer install --no-dev --optimize-autoloader

if exist package.json (
    echo Installazione dipendenze frontend...
    call npm ci
    call npm run build
)

:: 4. Configurazione Database
echo [4/5] Migrazione Database...
if not exist database.sqlite (
    type nul > database.sqlite
)
call vendor\bin\phinx migrate -e production

:: 5. Diagnostica Finale
echo [5/5] Esecuzione Universal Doctor...
php migrazione_totale\universal_doctor.php

echo ==================================================
echo    INSTALLAZIONE COMPLETATA!
echo ==================================================
echo Per avviare il server di test rapido:
echo run start_server.bat
echo.
pause
