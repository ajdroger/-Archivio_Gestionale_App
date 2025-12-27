@echo off
TITLE Fratellanza Militare - UNIVERSAL MIGRATION TOOL
COLOR 0A
CLS

ECHO ========================================================
ECHO    FRATELLANZA MILITARE - MIGRATION WIZARD
ECHO ========================================================
ECHO.
ECHO Questo script preparera' il progetto per l'esecuzione
ECHO su questo computer (PC Universita' o altro).
ECHO.
ECHO Operazioni che verranno svolte:
ECHO 1. Controllo Versione PHP
ECHO 2. Installazione Dipendenze (Composer/NPM)
ECHO 3. Verifica Integrita' File
ECHO 4. Controllo Database
ECHO.
PAUSE

php "%~dp0\universal_doctor.php"

EXIT
