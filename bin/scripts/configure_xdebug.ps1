# Script per configurare Xdebug nel php.ini
$phpIniPath = "C:\Program Files\Ampps\php82\php.ini"

# Leggi il contenuto corrente
$content = Get-Content $phpIniPath -Raw

# Rimuovi configurazioni xdebug esistenti
$content = $content -replace "(?m)^\[Xdebug\].*?(?=\r?\n\[|\r?\n\r?\n|$)", ""
$content = $content -replace "(?m)^zend_extension=.*xdebug.*$", ""
$content = $content -replace "(?m)^xdebug\..*$", ""
$content = $content -replace "(?m)^;Xdebug.*$", ""
$content = $content -replace "(?m)^;zend_extension=.*xdebug.*$", ""

# Pulisci righe vuote multiple
$content = $content -replace "(\r?\n){3,}", "`r`n`r`n"

# Aggiungi la configurazione corretta alla fine
$xdebugConfig = @"

[Xdebug]
zend_extension="C:/Program Files/Ampps/php82/lib/php_xdebug.dll"
xdebug.mode=coverage,develop,debug
xdebug.client_port=9003
xdebug.start_with_request=yes
xdebug.log="C:/Program Files/Ampps/www/MCAG_Militare-Civile-Archivio-Gestionale/logs/dev/xdebug.log"
xdebug.max_nesting_level=512
xdebug.cli_color=1
"@

$content = $content.TrimEnd() + "`r`n" + $xdebugConfig

# Scrivi il file aggiornato
Set-Content -Path $phpIniPath -Value $content

Write-Host "Configurazione Xdebug completata!"
Write-Host "File: $phpIniPath"
