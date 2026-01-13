# Guida: Creazione Repository GitHub Privata

## 📋 Panoramica
Questa guida ti aiuterà a creare una repository privata su GitHub e caricare il progetto **fratellanza-militare-archivio**.

## ✅ Lavoro Completato
- ✓ `.gitignore` migliorato con esclusioni complete per file sensibili
- ✓ Tutti i file staged e committati nel repository locale
- ✓ Repository Git locale pronto per il push

## 🔐 Passo 1: Crea la Repository su GitHub

### Opzione A: Via Browser (Consigliata)

1. **Accedi a GitHub**
   - Vai su [github.com](https://github.com) ed effettua il login

2. **Crea Nuova Repository**
   - Clicca sul pulsante **"+"** in alto a destra
   - Seleziona **"New repository"**
   
3. **Configura la Repository**
   ```
   Repository name: fratellanza-militare-archivio
   Description: Sistema di Gestione Archivio per MCAG - Enterprise-grade membership and document management system
   Visibility: ☑️ Private (IMPORTANTE!)
   
   ⚠️ NON selezionare:
   ❌ Add a README file
   ❌ Add .gitignore
   ❌ Choose a license
   ```
   
4. **Crea Repository**
   - Clicca su **"Create repository"**

5. **Copia l'URL della Repository**
   - Dopo la creazione, vedrai una pagina con le istruzioni
   - Copia l'URL HTTPS che apparirà in questo formato:
     ```
     https://github.com/TUO_USERNAME/fratellanza-militare-archivio.git
     ```

### Opzione B: Via GitHub CLI (Se Installato)

Se preferisci installare GitHub CLI:

```powershell
# Installa GitHub CLI tramite winget
winget install --id GitHub.cli

# Autentica
gh auth login

# Crea repository privata
gh repo create fratellanza-militare-archivio --private --source=. --remote=origin --push
```

## 🔗 Passo 2: Collega il Repository Locale a GitHub

Dopo aver creato la repository su GitHub, esegui questi comandi nel terminale:

### Verifica Remote Esistente
```powershell
cd 'c:\Program Files\Ampps\www\fratellanza-militare-archivio'
git remote -v
```

### Rimuovi Remote Esistente (se presente)
```powershell
git remote remove origin
```

### Aggiungi Nuovo Remote GitHub
> ⚠️ **IMPORTANTE**: Sostituisci `TUO_USERNAME` con il tuo username GitHub!

```powershell
git remote add origin https://github.com/TUO_USERNAME/fratellanza-militare-archivio.git
```

### Verifica Remote
```powershell
git remote -v
```

Dovresti vedere:
```
origin  https://github.com/TUO_USERNAME/fratellanza-militare-archivio.git (fetch)
origin  https://github.com/TUO_USERNAME/fratellanza-militare-archivio.git (push)
```

## 📤 Passo 3: Push del Codice su GitHub

### Push Iniziale
```powershell
git push -u origin master
```

> **Nota**: Ti potrebbe essere chiesto di autenticarti. Se usi HTTPS, avrai bisogno di un Personal Access Token (PAT) invece della password.

### Creazione Personal Access Token (se necessario)

Se GitHub richiede un token:

1. Vai su: https://github.com/settings/tokens
2. Click **"Generate new token"** → **"Generate new token (classic)"**
3. Configura:
   ```
   Note: MCAG Archivio
   Expiration: 90 days (o come preferisci)
   Scopes: ☑️ repo (seleziona tutto sotto "repo")
   ```
4. Click **"Generate token"**
5. **COPIA IL TOKEN** (lo vedrai una sola volta!)
6. Usa il token come password quando fai il push

## ✅ Passo 4: Verifica Upload

### Controlla Status Finale
```powershell
git status
```

Dovresti vedere: `Your branch is up to date with 'origin/master'.`

### Verifica su GitHub
1. Vai su `https://github.com/TUO_USERNAME/fratellanza-militare-archivio`
2. Verifica che tutti i file siano stati caricati
3. Controlla che la repository sia marcata come **Private** (icona lucchetto)

## 📊 Struttura Caricata

La repository conterrà:
```
fratellanza-militare-archivio/
├── 📁 bin/                     # Script di utilità e diagnostica
├── 📁 config/                  # Configurazioni dell'applicazione
├── 📁 db/                      # Migration e schema database
├── 📁 Documentazione/          # Documentazione completa del progetto
├── 📁 public/                  # Asset pubblici e entry point
├── 📁 resources/               # Risorse (CSS, JS, immagini)
├── 📁 src/                     # Codice sorgente PHP
├── 📁 templates/               # Template Mustache
├── 📁 tests/                   # Suite completa di test (Pest/PHPUnit)
├── 📄 .gitignore               # File e directory esclusi
├── 📄 composer.json            # Dipendenze PHP
├── 📄 package.json             # Dipendenze Node.js
├── 📄 phpunit.xml              # Configurazione test
└── 📄 README.md                # Documentazione principale
```

## ⚠️ File Esclusi (Tramite .gitignore)

I seguenti file **NON** verranno caricati (per sicurezza):
- ✗ `.env` (credenziali e segreti)
- ✗ `vendor/` (dipendenze PHP)
- ✗ `node_modules/` (dipendenze Node)
- ✗ `database.sqlite` (database locale)
- ✗ `logs/` (file di log)
- ✗ `backups/` (backup del sistema)
- ✗ File temporanei e cache

## 🔄 Comandi Futuri

### Pull (Recuperare modifiche)
```powershell
git pull origin master
```

### Push (Inviare modifiche)
```powershell
git add .
git commit -m "Descrizione delle modifiche"
git push origin master
```

### Clonare la Repository (su altro computer)
```powershell
git clone https://github.com/TUO_USERNAME/fratellanza-militare-archivio.git
cd fratellanza-militare-archivio
composer install
npm install
cp .env.example .env
# Configura .env con le tue impostazioni
```

## 🎯 Prossimi Passi Consigliati

Dopo il caricamento su GitHub, considera:

1. **Aggiungi Collaboratori** (se necessario)
   - Settings → Collaborators → Add people

2. **Configura Branch Protection**
   - Settings → Branches → Add rule
   - Proteggi il branch `master` da push forzati

3. **Aggiungi GitHub Actions** (CI/CD)
   - Automated testing
   - Deployment automation

4. **Crea Issues e Projects**
   - Traccia bugs e feature
   - Organizza il lavoro

## 🆘 Risoluzione Problemi

### Errore: "Permission denied"
- Verifica username/password o token
- Assicurati di avere accesso alla repository

### Errore: "Repository not found"
- Verifica l'URL remote
- Controlla che la repository sia stata creata

### Errore: "Updates were rejected"
- Esegui `git pull origin master --rebase`
- Risolvi eventuali conflitti
- Esegui `git push origin master`

## 📧 Supporto

Per problemi o domande:
- GitHub Docs: https://docs.github.com
- Git Reference: https://git-scm.com/docs

