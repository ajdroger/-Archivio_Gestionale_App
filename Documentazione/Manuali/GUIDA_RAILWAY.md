# Guida: Pubblicazione su Railway

## 📋 Panoramica
Questa guida ti aiuterà a pubblicare il progetto **fratellanza-militare-archivio** su Railway, una piattaforma moderna per deployment di applicazioni che **supporta pienamente PHP con SQLite**.

## ✅ Perché Railway è Ideale per Questo Progetto

Railway è **perfetto** per questa applicazione perché:

| Caratteristica | Railway | Vercel |
|---------------|---------|--------|
| **SQLite Support** | ✅ Sì (con volumi) | ❌ No |
| **Filesystem Persistente** | ✅ Sì | ❌ No (effimero) |
| **Sessioni File-Based** | ✅ Sì | ❌ No |
| **File Upload Locali** | ✅ Sì | ❌ No |
| **Build Time** | ~2-3 min | ~1 min |
| **Costo Free Tier** | $5 credit/mese | Unlimited gratis |
| **Adatto per PHP** | ✅✅✅ Eccellente | ⚠️ Limitato |

> 🎯 **Raccomandazione**: Railway è la scelta migliore per questo progetto!

## 🔄 Prerequisiti

### 1. Repository GitHub
Il progetto deve essere su GitHub. Segui la [`GUIDA_GITHUB.md`](./GUIDA_GITHUB.md) se non l'hai già fatto.

### 2. Account Railway
- Registrati su [railway.app](https://railway.app)
- Collega il tuo account GitHub
- Free tier: $5 di crediti gratuiti al mese

### 3. File di Configurazione
Sono stati creati automaticamente:
- ✅ `nixpacks.toml` - Configurazione build
- ✅ `start.sh` - Script di avvio

## 📁 File Creati per Railway

### 1. nixpacks.toml
Configurazione del build system Railway (Nixpacks):
- PHP 8.2 con tutte le estensioni necessarie
- SQLite, PDO, mbstring, GD, etc.
- Composer install ottimizzato
- Setup directory e permessi

### 2. start.sh
Script di startup che:
- Crea directory necessarie (storage, logs, backups)
- Imposta permessi corretti
- Crea database SQLite se non esiste
- Esegue migrazioni automaticamente
- Avvia il server PHP

## 🚀 Deployment su Railway

### Metodo 1: Via Dashboard Railway (Consigliato)

#### Passo 1: Nuovo Progetto
1. Vai su [railway.app/new](https://railway.app/new)
2. Fai login con GitHub
3. Click **"Deploy from GitHub repo"**
4. Seleziona `fratellanza-militare-archivio`
5. Click **"Deploy Now"**

#### Passo 2: Configura Variabili d'Ambiente
1. Nel dashboard del progetto, vai su **Variables**
2. Aggiungi le seguenti variabili:

```bash
# App Configuration
APP_ENV=production
APP_NAME=Fratellanza Militare Archivio
APP_DEBUG=false

# Database (SQLite - già configurato)
DB_PATH=/app/database.sqlite

# TOTP 2FA Encryption Key
TOTP_ENCRYPTION_KEY=your-base64-encoded-32-byte-key

# SMTP Email Configuration
SMTP_HOST=smtp.gmail.com
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
SMTP_PORT=587
```

#### Passo 3: Configura Volume Persistente (Importante!)
Per mantenere il database SQLite tra i deploy:

1. Vai su **Settings** → **Volumes**
2. Click **"New Volume"**
3. Configura:
   ```
   Mount Path: /app/data
   ```
4. Salva

5. Aggiorna `DB_PATH` nelle variabili:
   ```bash
   DB_PATH=/app/data/database.sqlite
   ```

#### Passo 4: Configura Dominio (Opzionale)
1. Vai su **Settings** → **Domains**
2. Railway genera automaticamente un dominio: `your-app.up.railway.app`
3. Oppure aggiungi un dominio personalizzato

#### Passo 5: Deploy!
Railway farà automaticamente il deploy. Attendi il completamento (~2-3 minuti).

### Metodo 2: Via Railway CLI

```bash
# Installa Railway CLI
npm install -g @railway/cli

# Login
railway login

# Link al progetto (dalla directory del progetto)
cd 'c:\Program Files\Ampps\www\fratellanza-militare-archivio'
railway link

# Aggiungi variabili d'ambiente
railway variables set APP_ENV=production
railway variables set TOTP_ENCRYPTION_KEY=your-key
railway variables set SMTP_HOST=smtp.gmail.com
# ... altre variabili

# Deploy
railway up
```

## 📦 Configurazione Avanzata

### Volume Persistente per Upload e Backup

Per persistere upload e backup oltre al database:

1. **Crea Volume Addizionale** (se necessario)
   ```
   Mount Path: /app/storage
   ```

2. **Aggiorna Configurazione**
   Modifica i path nel codice per usare `/app/storage/uploads` e `/app/storage/backups`

### Database Migrations

Le migrazioni vengono eseguite automaticamente dallo script `start.sh`. Se vuoi eseguirle manualmente:

```bash
# Via Railway CLI
railway run vendor/bin/phinx migrate -e production
```

### Logs e Debugging

```bash
# Visualizza logs in tempo reale
railway logs

# Apri shell nel container
railway shell

# Esegui comandi
railway run php bin/diagnostics_runner.php
```

## 🔧 Configurazione Post-Deployment

### 1. Verifica Deployment
Visita l'URL fornito da Railway (es. `https://your-app.up.railway.app`)

### 2. Esegui Health Check
```bash
# Via browser
https://your-app.up.railway.app/health

# Via curl
curl https://your-app.up.railway.app/health
```

### 3. Accedi all'Admin
```
URL: https://your-app.up.railway.app/login
Username: (come configurato nel database)
Password: (come configurato nel database)
```

### 4. Configura 2FA
Se il sistema richiede 2FA, assicurati che `TOTP_ENCRYPTION_KEY` sia configurato.

## 🔄 Aggiornamenti e Redeploy

### Automatic Deployments
Railway fa automatic deploy ad ogni push su GitHub:

```bash
# Fai modifiche al codice
git add .
git commit -m "Update feature X"
git push origin master

# Railway detecta il push e fa auto-deploy
```

### Manual Redeploy
```bash
# Via CLI
railway up

# Via Dashboard
# Vai su Deployments → Click sui tre puntini → "Redeploy"
```

### Rollback
```bash
# Via Dashboard
# Vai su Deployments → Seleziona deployment precedente → "Redeploy"
```

## 📊 Monitoraggio

### Dashboard Railway
- **Metrics**: CPU, Memory, Network usage
- **Logs**: Real-time application logs
- **Deployments**: Storia dei deploy
- **Observability**: Performance insights

### Application Logs
```bash
# Tail logs
railway logs --tail 100

# Follow logs
railway logs -f
```

### Health Checks
Railway può configurare health checks automatici:

1. Vai su **Settings** → **Health Check**
2. Configura:
   ```
   Path: /health
   Interval: 60 seconds
   ```

## 💰 Costi e Limiti

### Free Tier
- $5 di crediti al mese
- ~500 ore di uptime
- 1 GB RAM
- 1 GB Storage

### Prova circa:
- $0.000231/min per vCPU
- $0.000231/GB-min per RAM
- **Stima**: ~$5-10/mese per uso moderato

### Hobby Plan
- $5/mese base
- Include $5 di crediti di utilizzo
- Ottimo per progetti personali

## 🎯 Ottimizzazioni

### 1. Reduce Build Time
```toml
# In nixpacks.toml - già configurato
[phases.install]
cmds = [
  "composer install --no-dev --optimize-autoloader --no-interaction"
]
```

### 2. Enable OPcache
Aggiungi nelle variabili d'ambiente:
```bash
PHP_OPCACHE_ENABLE=1
PHP_OPCACHE_MEMORY_CONSUMPTION=128
```

### 3. Compressione Output
Railway abilita automaticamente gzip compression.

### 4. CDN (Opzionale)
Per asset statici ad alto traffico, considera Cloudflare CDN davanti a Railway.

## 🆘 Risoluzione Problemi

### Errore: "Build Failed"
```bash
# Controlla i logs di build
railway logs --deployment <deployment-id>

# Verifica nixpacks.toml sia corretto
# Verifica composer.json sia valido
```

### Errore: "Database locked"
```bash
# SQLite può dare problemi con alta concorrenza
# Soluzione 1: Aumenta timeout
# Soluzione 2: Migra a PostgreSQL se necessario

# Aggiungi a config/container.php:
$pdo->setAttribute(PDO::ATTR_TIMEOUT, 30);
```

### Errore: "Permission Denied" su file
```bash
# Verifica che start.sh abbia permessi di esecuzione
chmod +x start.sh

# Verifica nelle variabili Railway:
RAILWAY_VOLUME_MOUNT_PATH=/app/data
```

### Errore: "Port already in use"
```bash
# Railway imposta automaticamente $PORT
# Verifica che start.sh usi: 0.0.0.0:$PORT
```

### Database Perso dopo Redeploy
```bash
# IMPORTANTE: Devi usare un Volume!
# Vai su Settings → Volumes → New Volume
# Mount Path: /app/data
# Aggiorna DB_PATH=/app/data/database.sqlite
```

### Session Errors
```bash
# Verifica che la directory sessions sia writable
# In start.sh (già configurato):
chmod -R 775 storage
```

## 🔐 Sicurezza

### HTTPS
✅ Railway fornisce HTTPS automaticamente per tutti i deployment

### Environment Variables
✅ Le variabili d'ambiente sono criptate e sicure
⚠️ Non committare `.env` nel repository

### Firewall
Railway automaticamente protegge con firewall. Solo le porte esposte sono accessibili.

### Rate Limiting
Il middleware di rate limiting del progetto funziona normalmente su Railway.

### Backup Automatici
Configura backup automatici del volume:

```bash
# Cron job per backup (da configurare nel codice)
0 2 * * * /app/bin/backup.sh
```

## 📈 Scalabilità

### Vertical Scaling
Aumenta risorse nel dashboard:
- Settings → Resources
- Modifica vCPU e RAM

### Horizontal Scaling
Railway supporta replica per alta disponibilità (Hobby+ plan)

### Database Scaling
Se SQLite diventa un bottleneck:
1. Migra a PostgreSQL (supportato nativamente da Railway)
2. Railway può provisionare PostgreSQL con un click
3. Usa `DATABASE_URL` invece di file SQLite

## 🔄 Migrazione da Locale a Railway

### 1. Esporta Database Locale
```bash
# Backup SQLite
cp database.sqlite database.backup.sqlite
```

### 2. Upload Database su Railway
```bash
# Via Railway CLI
railway run sqlite3 /app/data/database.sqlite < database.backup.sqlite

# Oppure usa lo shell interattivo
railway shell
# Poi copia il file manualmente
```

### 3. Verifica Dati
```bash
railway run php bin/diagnostics_runner.php
```

## 🎯 Checklist Pre-Deploy

- [ ] Repository su GitHub configurato e pushato
- [ ] `nixpacks.toml` presente nella root
- [ ] `start.sh` presente e con permessi esecuzione
- [ ] `.env.example` aggiornato
- [ ] Test locali completati
- [ ] Backup database creato
- [ ] Account Railway creato e GitHub collegato
- [ ] Variabili d'ambiente preparate
- [ ] Piano per volume persistente deciso

## 📚 Risorse

### Documentazione Railway
- [Railway Docs](https://docs.railway.app/)
- [PHP on Railway](https://docs.railway.app/languages/php)
- [Nixpacks](https://nixpacks.com/)
- [Volumes](https://docs.railway.app/reference/volumes)

### CLI
- [Railway CLI](https://docs.railway.app/develop/cli)
- [Railway CLI Reference](https://docs.railway.app/reference/cli-api)

### Community
- [Railway Discord](https://discord.gg/railway)
- [Railway Community](https://help.railway.app/)

## 🆚 Railway vs Vercel vs Tradizionale

| Caratteristica | Railway | Vercel | VPS Tradizionale |
|---------------|---------|--------|------------------|
| **Setup Complexity** | ⭐ Facile | ⭐ Facile | ⭐⭐⭐ Medio |
| **SQLite Support** | ✅ Sì | ❌ No | ✅ Sì |
| **Cost** | ~$5-10/mese | Gratis* | $5-20/mese |
| **Maintenance** | ⭐ Minima | ⭐ Minima | ⭐⭐⭐ Alta |
| **Scalability** | ⭐⭐ Buona | ⭐⭐⭐ Ottima | ⭐⭐ Manuale |
| **PHP Optimization** | ✅ Sì | ⚠️ Limitato | ✅ Completo |

> 🎯 **Per questo progetto**: Railway è il **sweet spot** tra facilità e funzionalità!

## 🎓 Tips e Best Practices

### 1. Use Environment-Specific Config
```php
// config/app.php
$isRailway = isset($_SERVER['RAILWAY_ENVIRONMENT']);
if ($isRailway) {
    // Railway-specific settings
}
```

### 2. Structured Logging
Railway cattura automaticamente stdout/stderr:
```php
error_log("INFO: User logged in"); // Appare nei Railway logs
```

### 3. Health Endpoints
```php
// routes.php - già presente
$app->get('/health', function($request, $response) {
    return $response->withJson(['status' => 'ok']);
});
```

### 4. Graceful Shutdown
```php
// Handle SIGTERM for graceful shutdown
pcntl_signal(SIGTERM, function() {
    // Cleanup code
    exit(0);
});
```

## 📧 Supporto

Per problemi o domande:
- Railway Support: https://help.railway.app/
- Railway Discord: https://discord.gg/railway
- Documentazione: https://docs.railway.app/

---

## 🚀 Quick Start Recap

```bash
# 1. Push su GitHub
git push origin master

# 2. Crea progetto su Railway
railway.app/new → Deploy from GitHub

# 3. Configura Volume
Settings → Volumes → New Volume → /app/data

# 4. Set Environment Variables
Variables → Add → APP_ENV, TOTP_ENCRYPTION_KEY, etc.

# 5. Deploy!
Railway auto-deploya! 🎉
```

✅ **Il tuo progetto è live su Railway!**
