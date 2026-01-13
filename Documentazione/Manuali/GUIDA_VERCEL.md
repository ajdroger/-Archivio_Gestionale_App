# Guida: Pubblicazione su Vercel

## 📋 Panoramica
Questa guida ti aiuterà a pubblicare il progetto **MCAG_Militare-Civile-Archivio-Gestionale** su Vercel utilizzando funzioni serverless PHP.

## ⚠️ Considerazioni Importanti

### Limitazioni di Vercel per PHP
Vercel è ottimizzato principalmente per applicazioni Node.js e framework frontend. Per PHP:
- ✅ Supporta PHP tramite runtime serverless
- ✅ Ottimo per API e applicazioni stateless
- ⚠️ **SQLite non è supportato** (filesystem effimero)
- ⚠️ Sessioni file-based richiedono alternative
- ⚠️ Upload di file richiede storage esterno

### Alternative Consigliate per Hosting PHP Completo
Se il progetto richiede tutte le funzionalità (SQLite, sessioni, upload):
- **DigitalOcean App Platform** - Supporto PHP completo
- **Railway** - Ottimo per PHP con database
- **Heroku** - Supporto PHP tradizionale
- **AWS Elastic Beanstalk** - Pieno controllo
- **VPS (DigitalOcean/Linode/Vultr)** - Massima flessibilità

## 🔄 Prerequisiti

### 1. Repository GitHub
Il progetto deve essere su GitHub (privato o pubblico). Segui la [`GUIDA_GITHUB.md`](./GUIDA_GITHUB.md) se non l'hai ancora fatto.

### 2. Account Vercel
- Registrati su [vercel.com](https://vercel.com)
- Collega il tuo account GitHub

### 3. Database Cloud (Obbligatorio)
Poiché SQLite non funziona su Vercel, devi migrare a un database cloud:

#### Opzione A: PlanetScale (MySQL Compatibile)
```bash
# 1. Crea account su planetscale.com
# 2. Crea un nuovo database
# 3. Ottieni connection string:
DATABASE_URL=mysql://username:password@host/database?sslaccept=strict
```

#### Opzione B: Supabase (PostgreSQL)
```bash
# 1. Crea account su supabase.com
# 2. Crea nuovo progetto
# 3. Ottieni connection string da Settings > Database
DATABASE_URL=postgresql://postgres:password@host:5432/postgres
```

#### Opzione C: Turso (SQLite-Compatibile su Cloud)
```bash
# 1. Crea account su turso.tech
# 2. Installa CLI: npm install -g @turso/cli
# 3. Crea database: turso db create mcag-archivio
# 4. Ottieni URL: turso db show mcag-archivio
TURSO_DATABASE_URL=libsql://your-database.turso.io
TURSO_AUTH_TOKEN=your-auth-token
```

## 📁 Preparazione del Progetto

### File Creati per Vercel

Sono stati aggiunti questi file al progetto:

1. **`vercel.json`** - Configurazione Vercel
2. **`api/index.php`** - Entry point serverless

### Modifiche Necessarie

#### 1. Aggiorna `composer.json`
Verifica che ci siano tutte le dipendenze necessarie:

```json
{
  "require": {
    "php": "^8.2"
  }
}
```

#### 2. Gestione Database
Dovrai modificare la configurazione del database per usare un DB cloud invece di SQLite.

**File da modificare:** `config/container.php`

```php
// Cambia da SQLite a MySQL/PostgreSQL
$container->set(PDO::class, function () {
    $databaseUrl = $_ENV['DATABASE_URL'] ?? '';
    
    if (str_starts_with($databaseUrl, 'mysql://')) {
        // PlanetScale o MySQL
        $pdo = new PDO($databaseUrl);
    } elseif (str_starts_with($databaseUrl, 'postgresql://')) {
        // Supabase o PostgreSQL
        $pdo = new PDO($databaseUrl);
    } else {
        throw new Exception('DATABASE_URL not configured for production');
    }
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
});
```

#### 3. Gestione Sessioni
Vercel richiede sessioni basate su database o Redis invece del filesystem.

**Opzione con Redis (Consigliato):**

```bash
# Usa Upstash Redis (free tier disponibile)
composer require predis/predis
```

```php
// Configura session handler
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', $_ENV['REDIS_URL'] ?? 'tcp://127.0.0.1:6379');
```

#### 4. Storage File (Upload)
Per file upload, usa un servizio cloud:

**Cloudinary (Immagini):**
```bash
composer require cloudinary/cloudinary_php
```

**AWS S3:**
```bash
composer require aws/aws-sdk-php
```

## 🚀 Deployment su Vercel

### Metodo 1: Via Dashboard Vercel (Consigliato)

1. **Accedi a Vercel**
   - Vai su [vercel.com/dashboard](https://vercel.com/dashboard)
   - Fai login con GitHub

2. **Importa Progetto**
   - Click **"Add New..."** → **"Project"**
   - Seleziona il repository `MCAG_Militare-Civile-Archivio-Gestionale`
   - Click **"Import"**

3. **Configura Progetto**
   ```
   Framework Preset: Other
   Build Command: composer install --no-dev --optimize-autoloader
   Output Directory: (lascia vuoto)
   Install Command: (lascia vuoto)
   ```

4. **Variabili d'Ambiente**
   Aggiungi le seguenti variabili in **Environment Variables**:
   
   ```bash
   # App
   APP_ENV=production
   APP_NAME=MCAG Archivio
   APP_URL=https://your-project.vercel.app
   
   # Database (PlanetScale/Supabase/Turso)
   DATABASE_URL=your_database_connection_string
   
   # Encryption
   ENCRYPTION_KEY=your-base64-encoded-32-byte-key
   
   # Email (se usi PHPMailer)
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=your-email@gmail.com
   MAIL_PASSWORD=your-app-password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=noreply@yourdomain.com
   MAIL_FROM_NAME="MCAG"
   
   # Redis (per sessioni - Upstash)
   REDIS_URL=redis://default:password@host:port
   
   # Storage (Cloudinary/S3)
   CLOUDINARY_URL=cloudinary://api_key:api_secret@cloud_name
   # oppure
   AWS_ACCESS_KEY_ID=your-key
   AWS_SECRET_ACCESS_KEY=your-secret
   AWS_DEFAULT_REGION=us-east-1
   AWS_BUCKET=your-bucket-name
   ```

5. **Deploy**
   - Click **"Deploy"**
   - Attendi il completamento del build
   - Visita l'URL fornito (es. `https://MCAG_Militare-Civile-Archivio-Gestionale.vercel.app`)

### Metodo 2: Via Vercel CLI

```powershell
# Installa Vercel CLI
npm install -g vercel

# Login
vercel login

# Deploy dalla directory del progetto
cd 'c:\Program Files\Ampps\www\MCAG_Militare-Civile-Archivio-Gestionale'
vercel

# Segui le istruzioni interattive:
# - Link to existing project? N
# - Project name: MCAG_Militare-Civile-Archivio-Gestionale
# - Override settings? N

# Deploy in produzione
vercel --prod
```

## 🔧 Configurazione Post-Deployment

### 1. Dominio Personalizzato
1. Vai su **Settings** → **Domains**
2. Aggiungi il tuo dominio
3. Configura DNS come indicato
4. Aggiorna `APP_URL` nelle variabili d'ambiente

### 2. Esegui Migrazioni Database
```bash
# Connettiti al database cloud e esegui le migrazioni
# Per PlanetScale:
phinx migrate -e production

# Oppure esegui manualmente gli script SQL su:
# - PlanetScale Dashboard
# - Supabase SQL Editor
# - Turso CLI
```

### 3. Configura CORS (se necessario)
Se hai un frontend separato, aggiungi headers CORS nel middleware.

## 📊 Monitoraggio e Logs

### Visualizza Logs
```bash
# Via CLI
vercel logs

# Via Dashboard
# Vai su Deployments → Select deployment → Logs
```

### Analytics
- Vai su **Analytics** tab per vedere traffico e performance
- **Speed Insights** per metriche di velocità

## 🔄 Aggiornamenti Futuri

### Automatic Deployments
Vercel fa automatic deploy ad ogni push su GitHub:

```bash
# Push su GitHub
git add .
git commit -m "Update feature X"
git push origin master

# Vercel detecta il push e fa auto-deploy
```

### Preview Deployments
Ogni Pull Request crea un deployment di preview automatico.

## ⚠️ Limitazioni e Considerazioni

### Timeout
- Funzioni serverless hanno timeout di 10s (free), 60s (Pro), 900s (Enterprise)
- Per operazioni lunghe (PDF generation, backup), considera async jobs

### Cold Starts
- Prima richiesta può essere lenta (cold start)
- Mitigazione: Vercel Edge Functions o caching

### File Storage
- No filesystem persistente
- Usa sempre storage cloud (S3, Cloudinary, etc.)

### Database Connections
- Limita il numero di connessioni
- Usa connection pooling quando possibile

## 🆘 Risoluzione Problemi

### Errore: "No Output Directory"
```bash
# Non serve output directory per PHP serverless
# Lascia il campo vuoto in Vercel dashboard
```

### Errore: "Class not found"
```bash
# Verifica che composer.json abbia autoload corretto
# Rebuild con: vercel --prod --force
```

### Errore: "Database connection failed"
```bash
# Verifica DATABASE_URL in Environment Variables
# Controlla che il database cloud sia accessibile
# Test connessione da locale con le stesse credenziali
```

### Errore: "Session handling failed"
```bash
# Verifica REDIS_URL se usi Redis sessions
# Oppure implementa database session handler
```

### Errore 500: Internal Server Error
```bash
# Controlla i logs:
vercel logs

# Verifica tutte le env variables siano settate
# Controlla che ENCRYPTION_KEY sia presente e valido
```

## 📈 Ottimizzazioni Consigliate

### 1. Caching
```php
// Usa Redis per cache
$redis = new Redis();
$redis->connect($_ENV['REDIS_URL']);
$redis->setex('cache_key', 3600, $data);
```

### 2. CDN per Asset
```javascript
// Vercel serve automaticamente asset statici via CDN
// public/css/* → Serviti via Edge Network
```

### 3. Minificazione
```bash
# Minifica CSS/JS prima del deploy
npm run build
```

### 4. Compressione
Vercel abilita automaticamente Brotli/Gzip compression.

## 🔐 Sicurezza

### HTTPS
✅ Vercel fornisce HTTPS automaticamente per tutti i deployment

### Environment Variables
✅ Le variabili d'ambiente sono criptate e sicure
⚠️ Non committare `.env` nel repository

### Rate Limiting
Implementa rate limiting per proteggere le API:
```php
// Già presente nel progetto
// Verifica src/Middleware/RateLimitMiddleware.php
```

## 📚 Risorse

### Documentazione
- [Vercel PHP Runtime](https://vercel.com/docs/functions/serverless-functions/runtimes/php)
- [Environment Variables](https://vercel.com/docs/projects/environment-variables)
- [Custom Domains](https://vercel.com/docs/custom-domains)

### Database Cloud
- [PlanetScale Docs](https://docs.planetscale.com/)
- [Supabase Docs](https://supabase.com/docs)
- [Turso Docs](https://docs.turso.tech/)

### Storage
- [Cloudinary PHP](https://cloudinary.com/documentation/php_integration)
- [AWS S3 PHP](https://docs.aws.amazon.com/sdk-for-php/)

## 🎯 Checklist Pre-Deploy

Prima di fare il deploy, assicurati di:

- [ ] Repository su GitHub configurato
- [ ] Database cloud configurato (PlanetScale/Supabase/Turso)
- [ ] Redis configurato per sessioni (Upstash)
- [ ] Storage cloud configurato per upload (S3/Cloudinary)
- [ ] Tutte le variabili d'ambiente preparate
- [ ] Migrations del database eseguite
- [ ] `vercel.json` e `api/index.php` presenti
- [ ] File `.env.example` aggiornato con le nuove variabili
- [ ] Test locali completati
- [ ] Backup del database attuale creato

## 📧 Supporto

Per problemi o domande:
- Vercel Support: https://vercel.com/support
- Vercel Community: https://github.com/vercel/vercel/discussions
- Documentazione PHP: https://vercel.com/docs/functions/serverless-functions/runtimes/php

