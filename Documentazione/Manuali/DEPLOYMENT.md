# 🚀 DEPLOYMENT GUIDE - MCAG (v2.0)

## 📋 Pre-Deployment Checklist

### 1. Environment Configuration (`.env`)
Assicurati che il file `.env` sia configurato per la produzione:
```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://archivio.fratellanza.it

# Database (MySQL Obbligatorio per v2.0)
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=fratellanza_prod
DB_USERNAME=secure_user
DB_PASSWORD=secure_pass

# Security
TOTP_ENCRYPTION_KEY=def000... (Generare con bin/maintenance/regenerate_key_clean.php)

# Mail (SMTP per 2FA e Notifiche)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=email@domain.com
SMTP_PASS=app_password
```

### 2. File Permissions
```bash
# Proteggi file sensibili
chmod 600 .env
chmod 600 auth.json

# Directory scrivibili
chmod -R 775 storage logs
chown -R www-data:www-data storage logs
```

---

## ☁️ Deployment Options

### Opzione A: Server Tradizionale (VPS/Linux)
1.  **Clone & Install**:
    ```bash
    git clone https://github.com/ajdroger/-Archivio_Gestionale_App.git .
    composer install --no-dev --optimize-autoloader
    ```
2.  **Database Migration**:
    Poiché il sistema è migrato da SQLite a MySQL, assicurati di importare lo schema iniziale:
    ```bash
    mysql -u user -p database < db/schema_initial.sql
    ```
3.  **Vhost Apache**:
    Punta il DocumentRoot a `/public`.

### Opzione B: Railway / Cloud (Docker)
Il progetto include configurazione Docker-ready.
1.  Collega la repo GitHub a Railway.
2.  Imposta le variabili d'ambiente nella dashboard di Railway.
3.  Il servizio `Nixpacks` o `Dockerfile` costruirà l'immagine.
4.  Collega un servizio MySQL Addon.

---

## 🛡️ Post-Deployment Verification (CLI Tools)

Esegui questi comandi sul server per verificare che tutto sia corretto:

1.  **System Health**:
    ```bash
    php bin/tools/health_check.php
    ```
2.  **Security Audit**:
    ```bash
    php bin/tools/security_audit_cli.php
    ```
3.  **SMTP Verification**:
    ```bash
    php bin/tools/test_smtp.php
    ```

---

## 🔄 Maintenance & Backups

### Automated Backups
Il sistema ha uno script di backup integrato che supporta retention policy (7 giorni).
Configura un CRON daily:
```cron
0 3 * * * php /path/to/app/bin/maintenance/backup_daily.php >> /path/to/app/logs/backup.log 2>&1
```

### Updates
Per aggiornare l'applicazione:
```bash
git pull origin master
composer install --no-dev
php bin/tools/health_check.php
```

