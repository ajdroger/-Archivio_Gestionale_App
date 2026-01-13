# ⚡ SETUP RAPIDO - v2.0 Enterprise

Questa guida ti permette di avere un'istanza funzionante di **Mission Control v2.0** in meno di 5 minuti.

## Prerequisiti
- **XAMPP / AMPPS** (con PHP 8.2+ e MySQL)
- **Composer** (installato nel PATH)
- **Git** (opzionale, per clonare)

## Procedura Passo-Passo

### 1. Preparazione Cartella
```powershell
cd "c:\Program Files\Ampps\www"
git clone https://github.com/ajdroger/-Archivio_Gestionale_App.git fratellanza-militare-archivio
cd fratellanza-militare-archivio
```

### 2. Installazione Dipendenze
```powershell
composer install --no-dev
npm install --omit=dev
```

### 3. Configurazione Ambiente
Copia il file di esempio dalla cartella config:
```powershell
copy config\.env.example .env
```
Apri `.env` e configura:
- `DB_DATABASE=fratellanza`
- `DB_USERNAME=root` (o utente locale)
- `DB_PASSWORD=mysql` (o password locale)

### 4. Setup Database
Importa lo schema database iniziale (se non hai migrazioni Phinx attive):
```powershell
# Esempio via CLI MySQL
mysql -u root -p fratellanza < db/schema.sql
```
*Oppure usa phpMyAdmin per importare `db/schema.sql`.*

### 5. Generazione Chiavi Sicurezza
Esegui lo script di manutenzione per generare le chiavi di crittografia (TOTP, Sessioni):
```powershell
php bin/maintenance/regenerate_key_clean.php
```

### 6. Verifica Installazione
Lancia i tool di diagnostica per assicurarti che tutto sia OK:
```powershell
php bin/tools/health_check.php
```
Se vedi tutti ✅, sei pronto!

---

## 🚀 Avvio Server
Poiché la configurazione Apache potrebbe essere complessa, usa il server interno PHP per test immediati:

```powershell
php -S localhost:8000 -t public
```

Apri il browser su: [http://localhost:8000](http://localhost:8000)

**Credenziali Default:**
- **User:** `admin`
- **Pass:** `admin123`
- **2FA:** (Al primo login ti verrà chiesto di scansionare il QR Code)

