# ⚠️ AZIONE RICHIESTA: Configurazione TOTP Encryption Key

## Problema
Il file `.env` **NON contiene** la chiave `TOTP_ENCRYPTION_KEY` necessaria per il 2FA.

## Soluzione

Apri il file `.env` e aggiungi questa riga:

```env
TOTP_ENCRYPTION_KEY="def00000ad42f32a37b7777b594894351d5a99f10a07e54c3972fc23596eaa3fd753ef8df3dc6ca27f94f7bf4fd8f56ea4cee1ba038c85ddf1c17f83787ae1265f1b750c9"
```

## File `.env` Completo

Copia questo contenuto COMPLETO nel tuo `.env`:

```env
APP_ENV=local
APP_DEBUG=true
APP_NAME="Fratellanza Militare Archivio"
APP_URL=http://localhost:8000

# Database Configuration (MySQL/MariaDB Required)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fratellanza_db
DB_USERNAME=root
DB_PASSWORD=

# Redis Configuration (Optional - for caching & rate limiting)
# Set to false if Redis is not installed
REDIS_ENABLED=false
REDIS_SCHEME=tcp
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_DB=0

# TOTP 2FA Encryption Key - IMPORTANTE!
TOTP_ENCRYPTION_KEY="def00000ad42f32a37b7777b594894351d5a99f10a07e54c3972fc23596eaa3fd753ef8df3dc6ca27f94f7bf4fd8f56ea4cee1ba038c85ddf1c17f83787ae1265f1b750c9"

# SMTP Email Configuration
SMTP_HOST=smtp.example.com
SMTP_USER=user@example.com
SMTP_PASS=secret
SMTP_PORT=587
```

## ⚠️ MOLTO IMPORTANTE

**NON rigenerare** questa chiave se gli utenti hanno già configurato il 2FA, altrimenti perderanno l'accesso!

## Dopo aver aggiunto la chiave

1. Salva il file `.env`
2. Riavvia il server PHP (se necessario)
3. Riprova il login con credenziali:
   - Username: `admin`
   - Password: `password`

Il sistema ti chiederà il codice 2FA dall'app Authenticator.

---

**Status**: ⏳ Aspettando che tu aggiunga la chiave al file `.env`
