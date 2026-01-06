# Guida alla Migrazione e Deployment

Questo pacchetto contiene tutto il necessario per migrare e installare l'applicazione gestionale **Fratellanza Militare Archivio v2.3** su un nuovo ambiente server (Locale, VPS, Cloud).

## Contenuto della Cartella

- `setup_windows.bat`: Script di installazione automatica per ambienti Windows.
- `setup_linux.sh`: Script di installazione automatica per ambienti Linux (Ubuntu/Debian/CentOS).
- `requirements.txt`: Lista dei requisiti di sistema.
- `env.production.example`: File di configurazione modello per l'ambiente di produzione.
- `universal_doctor.php`: Tool di diagnostica per verificare la salute del sistema post-installazione.

## Istruzioni di Installazione

### 1. Prerequisiti
Assicurati che il server di destinazione soddisfi i requisiti elencati in `requirements.txt`.
- PHP 8.2 o superiore
- Composer
- Web Server (Apache, Nginx, IIS o Caddy)

### 2. Copia dei File
Copia l'intera cartella del progetto (`fratellanza-militare-archivio`) sul server di destinazione.

### 3. Procedura di Setup

#### Windows
1. Apri un terminale (CMD o PowerShell) nella cartella `migrazione_totale`.
2. Esegui `setup_windows.bat`.
3. Segui le istruzioni a schermo.

#### Linux / macOS
1. Apri un terminale.
2. Dai i permessi di esecuzione allo script: `chmod +x migrazione_totale/setup_linux.sh`.
3. Esegui lo script: `./migrazione_totale/setup_linux.sh`.

### 4. Configurazione Web Server

#### Apache
Assicurati che `mod_rewrite` sia abilitato. La cartella `public/` contiene già un `.htaccess` preconfigurato.
Punta il DocumentRoot del VirtualHost alla cartella `public/` del progetto.

#### Nginx
Esempio di configurazione server block:
```nginx
server {
    listen 80;
    server_name gestionale.fratellanza.it;
    root /var/www/fratellanza-militare-archivio/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }
}
```

## Post-Installazione
Dopo l'installazione, esegui sempre `php migrazione_totale/universal_doctor.php` per verificare che:
- La connessione al database sia attiva.
- I permessi delle cartelle siano corretti.
- Le dipendenze siano caricate.
