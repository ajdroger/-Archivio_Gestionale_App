# Fratellanza Militare - Archivio Digitale Soci
> **Edizione: Mission-Critical Enterprise (v2.0)**

Sistema professionale di gestione e digitalizzazione dell'archivio storico e corrente della Fratellanza Militare di Firenze.

## 🚀 Panoramica
L'applicazione è progettata per operare in scenari ad alta affidabilità:
- **Integrità Transazionale**: Salvataggi atomici "tutto-o-niente" (MySQL InnoDB).
- **Tracciabilità Totale**: Ogni operazione è marcata con un **Request Correlation ID**.
- **Disaster Recovery**: Backup off-site automatici (Cloud Sync) e rotazione locale.
- **Sicurezza Hardening**: 
    - 2FA (TOTP) con **Secrets Encrypted (AES-256)**.
    - Password Hashing (Bcrypt).
    - Session Hardening (Secure/HttpOnly).
- **OCR & Digitalizzazione**: Motore OCR integrato per la dematerializzazione.
- **GDPR Compliance**: Pseudonimizzazione, Diritto all'Oblio e Audit Log immutabile.

## 🛠️ Requisiti di Sistema
- **PHP**: 8.2 o superiore.
- **Estensioni PHP**: `pdo_mysql`, `json`, `mbstring`, `openssl`.
- **Database**: MySQL 8.0+.
- **Composer**: Gestione dipendenze.
- **Web Server**: Apache/Ampps/Nginx.

## 📦 Installazione & Setup
1. **Composer**: `composer install`
2. **Setup Env**: `cp .env.example .env` (Configura `TOTP_SECRET` e `DB_PATH`).
3. **Migrazioni**: `vendor/bin/phinx migrate`
4. **Permessi**: Assicurati che `storage/`, `logs/` e `database.sqlite` siano scrivibili.

## 🧪 Testing e Diagnostica Mission-Critical
Il sistema dispone di una suite di controllo qualità avanzata:
- **Suite di Test (Pest)**: Esegue 71 test (Unit, Feature, Integration, Security).
  ```bash
  vendor/bin/pest
  ```
- **Analisi Statica (PHPStan Level 5)**: Verifica formale della logica e dei tipi.
  ```bash
  vendor/bin/phpstan analyse src
  ```
- **Mission-Critical Console (CLI)**: Hub centralizzato per manutenzione e incident response.
  ```bash
  php bin/debug_console/console.php
  ```
- **Developer Dashboard (Web)**: Accessibile via `/devtools` per il monitoraggio della resilienza.

## 🔒 Sicurezza e Privacy
- **Audit Logging**: Registrazione SQL-based con pseudonimizzazione automatica (GDPR).
- **Protezione Sessioni**: Cookie configurati con `SameSite=Strict` e `HttpOnly`.
- **Storage Lockdown**: Directory `/storage/uploads` protetta da esecuzione script e accesso diretto via `.htaccess`.

---
*Digitalizzazione a cura di Soobadur Mohammad Ajmeer - Tecnico Informatico*
