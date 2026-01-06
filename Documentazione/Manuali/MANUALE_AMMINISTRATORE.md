# 📘 MANUALE UTENTE - AMMINISTRATORE
## Sistema di Gestione Archivio Fratellanza Militare

**Versione:** 2.0  
**Data:** Gennaio 2026  
**Autore:** Soobadur Mohammad Ajmeer ©  
**Destinatari:** Amministratori di Sistema  
**Livello Accesso:** Completo (*)

---

## 📋 INDICE

1. [Introduzione](#introduzione)
2. [Accesso al Sistema](#accesso)
3. [Dashboard Amministrativa](#dashboard)
4. [Gestione Soci](#gestione-soci)
5. [Gestione Documenti](#gestione-documenti)
6. [Gestione Utenti e Permessi](#gestione-utenti)
7. [Sistema di Sicurezza](#sicurezza)
8. [DevTools e Debug](#devtools)
9. [Report e Statistiche](#report)
10. [Backup e Manutenzione](#backup)
11. [API Management](#api)
12. [Risoluzione Problemi](#troubleshooting)

---

## 🌟 INTRODUZIONE {#introduzione}

Come **Amministratore**, hai accesso completo a tutte le funzionalità del sistema. Le tue responsabilità includono:

- ✅ Gestione completa anagrafica soci
- ✅ Amministrazione utenti e permessi
- ✅ Configurazione sistema e sicurezza
- ✅ Monitoraggio e debug
- ✅ Gestione API e integrazioni
- ✅ Backup e disaster recovery

**⚠️ IMPORTANTE**: Con grandi poteri derivano grandi responsabilità. Le azioni amministrative sono tutte tracciate nel sistema di Audit Log.

---

## 🔐 ACCESSO AL SISTEMA {#accesso}

### 1.1 Login Iniziale

1. Apri il browser e vai all'URL del sistema
2. Inserisci le credenziali di amministratore:
   - **Username**: `admin`
   - **Password**: (fornita dal sistemista)

### 1.2 Autenticazione a Due Fattori (2FA)

Al primo accesso, il sistema richiederà la configurazione 2FA:

**Procedura Setup 2FA**:
1. Scarica un'app authenticator (Google Authenticator, Authy, Microsoft Authenticator)
2. Scansiona il QR Code mostrato a schermo
3. Inserisci il codice a 6 cifre generato dall'app
4. **IMPORTANTE**: Salva i codici di backup in luogo sicuro

**Login Successivi**:
1. Username + Password
2. Codice OTP dall'app (6 cifre)

### 1.3 Gestione Sessione

- **Durata Sessione**: 8 ore di inattività
- **Logout Automatico**: Dopo timeout o chiusura browser
- **Logout Manuale**: Click sul menu utente → "Esci"

---

## 📊 DASHBOARD AMMINISTRATIVA {#dashboard}

Dopo il login, accedi alla **Dashboard Amministrativa** che mostra:

### Panoramica Generale
- 📈 **Statistiche Soci**: Totali, Attivi, Morosi, Nuovi iscritti
- 📊 **Grafici Trend**: Iscrizioni mensili, distribuzione demografica
- ⚠️ **Alert Sistema**: Errori critici, backups, storage
- 🔔 **Notifiche**: Azioni recenti, documenti in attesa

### Widget Rapidi
- 🆕 **Nuovo Socio**: Accesso rapido a creazione record
- 📄 **Documenti Recenti**: Ultimi caricamenti
- 👥 **Utenti Online**: Operatori attivi
- 🔍 **Ricerca Veloce**: Barra ricerca globale

---

## 👥 GESTIONE SOCI {#gestione-soci}

### 3.1 Visualizzazione Archivio

**Menu**: `Archivio → Soci`

**Funzionalità**:
- **Tabella Completa**: Lista paginata con tutti i soci
- **Filtri Avanzati**:
  - Stato (Attivo, Sospeso, Cancellato)
  - Morosità (Paganti, Morosi)
  - Anno iscrizione
- **Ricerca**: Per Nome, Cognome, CF, Matricola, Email, Telefono
- **Ordinamento**: Click su intestazioni colonne

**Azioni Disponibili**:
- 👁️ **Visualizza**: Scheda completa socio
- ✏️ **Modifica**: Aggiornamento dati anagrafici
- 🗑️ **Elimina** (Soft Delete): Nasconde socio senza perdere dati
- 📄 **Genera PDF**: Modulo iscrizione

### 3.2 Creazione Nuovo Socio

**Procedura Step-by-Step**:

1. Click su **"+ Nuovo Socio"**
2. Compila la scheda anagrafica:

   **Dati Identificativi**:
   - Nome* (es: Mario)
   - Cognome* (es: Rossi)
   - Data Nascita* (formato gg/mm/aaaa)
   - Sesso* (M/F)
   - Luogo Nascita* (Comune o Stato estero)
   - Codice Fiscale* (16 caratteri maiuscole)
     - **Tip**: Usa il pulsante "Calcola Automatico" per generarlo

   **Reti di Contatto**:
   - Indirizzo (es: Via Roma 1, 50123 Firenze)
   - Email (validazione automatica)
   - Telefono (formato +39 333 1234567)
   - Matricola (auto-generata se vuota)

3. **Pagamento Quota Associativa**:
   - ✅ Attiva il toggle se il socio ha pagato
   - Questo genera automaticamente il PDF modulo

4. Click **"COMMIT RECORD"**

**✅ Risultato**: 
- Socio creato con ID univoco
- Se pagamento attivo: PDF generato in `storage/uploads/`
- Email notifica (se configurata)
- Audit log registrato

### 3.3 Modifica Socio Esistente

1. Cerca il socio (ricerca o tabella)
2. Click sull'icona **✏️ Modifica**
3. Aggiorna i campi necessari
4. **Salva Modifiche**

**⚠️ Nota**: Codice Fiscale non modificabile dopo creazione (chiave primaria)

### 3.4 Eliminazione Socio (Soft Delete)

**Scenario 1: Soft Delete** (raccomandato)
1. Click su **🗑️ Elimina**
2. Conferma l'azione
3. ✅ Il socio viene nascosto ma dati conservati
4. Recuperabile da DevTools → Database Inspector

**Scenario 2: Hard Delete GDPR**
1. Menu **DevTools → Security Panel**
2. Tab "GDPR Compliance"
3. Inserisci Codice Fiscale
4. Click **"Hard Delete Permanente"**
5. ⚠️ **ATTENZIONE**: Azione irreversibile, tutti i dati e file vengono cancellati
6. Audit log generato automaticamente

### 3.5 Gestione Morosità

**Identificazione Morosi**:
- Dashboard: Widget "Soci Morosi"
- Archivio: Filtro "Morosità = Morosi"
- Icona ⚠️ rossa nella tabella

**Procedura Regolarizzazione**:
1. Apri scheda socio moroso
2. Tab "Documenti"
3. Upload nuovo "Modulo Iscrizione" anno corrente
4. Stato documento: "Validato"
5. ✅ Socio automaticamente rimosso da lista morosi

---

## 📄 GESTIONE DOCUMENTI {#gestione-documenti}

### 4.1 Visualizzazione Documenti Socio

1. Apri scheda socio
2. Tab **"Documenti Associati"**
3. Visualizzi: Nome file, Tipo, Data caricamento, Stato, Anno solare

### 4.2 Caricamento Nuovo Documento

**Procedura**:
1. Scheda socio → Tab "Documenti"
2. Click **"+ Carica Documento"**
3. Compila form:
   - **Tipo Documento**: Modulo Iscrizione / Documento Generico
   - **File**: PDF o Immagine (max 10MB)
   - **Anno Solare**: Anno di riferimento (default: corrente)
   - **Quota Versata**: Se modulo iscrizione con pagamento
   - **Consenso GDPR**: Flag obbligatori
4. Click **"Upload"**

**Validazioni**:
- ✅ Formato: PDF, JPG, PNG
- ✅ Dimensione < 10MB
- ✅ Virus scan (se configurato)

### 4.3 Download e Visualizzazione

- **Download**: Click sull'icona 📥 accanto al documento
- **Anteprima**: Click sul nome file (se PDF)

### 4.4 Eliminazione Documento

1. Click sull'icona **🗑️** accanto al documento
2. Conferma eliminazione
3. ✅ File rimosso da storage, record cancellato

---

## 👤 GESTIONE UTENTI E PERMESSI {#gestione-utenti}

### 5.1 Visualizzazione Utenti Sistema

**Menu**: `DevTools → Security Panel → Gestione Utenti`

**Tabella Utenti**:
- Username
- Ruolo (Amministratore / Operatore / Utente)
- 2FA Attivo
- Ultimo Accesso
- Stato (Attivo / Bloccato)

### 5.2 Creazione Nuovo Utente

**Procedura**:
1. Security Panel → **"+ Crea Utente"**
2. Compila:
   - **Username**: Univoco, 5-20 caratteri alfanumerici
   - **Password**: Minimo 8 caratteri, maiuscole, numeri, simboli
   - **Ruolo**: 
     - `Amministratore`: Accesso completo
     - `Operatore`: Gestione soci e documenti
     - `Utente`: Sola lettura
   - **Email**: Per recupero password
3. Click **"Crea"**

**✅ Risultato**:
- Utente creato con credenziali temporanee
- Email inviata con istruzioni primo login
- 2FA da configurare al primo accesso

### 5.3 Modifica Permessi

Gli **Amministratori** possono modificare dinamicamente i permessi:

**Permessi Standard**:
- `soci.read`: Visualizza soci
- `soci.update`: Modifica soci
- `soci.delete`: Elimina soci
- `documenti.create`: Carica documenti
- `documenti.delete`: Elimina documenti
- `report.generate`: Genera report
- `*`: Accesso completo (solo admin)

**Assegnazione**:
1. Security Panel → Tab "ACL (Access Control)"
2. Seleziona Ruolo
3. Check/Uncheck permessi
4. **Salva Modifiche**

### 5.4 Reset Password Utente

**Scenario: Utente ha dimenticato password**

1. Security Panel → Trova utente
2. Click **"Reset Password"**
3. Scegli:
   - **Genera Password Temporanea**: Sistema crea password casuale
   - **Invia Email Reset**: Link reset inviato a email utente
4. Conferma

### 5.5 Blocco/Sblocco Utente

**Blocco** (per sospetto accesso non autorizzato):
1. Trova utente
2. Click **"Blocca Utente"**
3. Sessioni terminate immediatamente
4. Login negato fino a sblocco

**Sblocco**:
1. Click **"Sblocca Utente"**
2. Utente può accedere nuovamente

---

## 🔒 SISTEMA DI SICUREZZA {#sicurezza}

### 6.1 Monitoraggio Audit Log

**Menu**: `DevTools → Audit Trail`

**Visualizzazione Log**:
- **Timestamp**: Data e ora evento
- **Utente**: Chi ha eseguito l'azione (pseudonimizzato se GDPR)
- **Azione**: Tipo evento (LOGIN, SOCIO_CREATE, PERMISSION_GRANT, ecc.)
- **Dettagli**: JSON con parametri
- **IP Address**: Origine richiesta

**Filtri**:
- Per Utente
- Per Azione
- Per Intervallo Date
- Per IP

**Export**:
- **JSON**: Click "Esporta JSON"
- **CSV**: Click "Esporta CSV"

### 6.2 Gestione API Keys

**Menu**: `DevTools → Security Panel → API Keys`

**Creazione Nuova API Key**:
1. Click **"+ Genera API Key"**
2. Compila:
   - **Nome**: Descrittivo (es: "Mobile App iOS")
   - **Scope**: Elenco endpoint permessi (es: `soci.read,soci.update`)
   - **Rate Limit**: Richieste/minuto (default: 60)
   - **Scadenza**: Data validità (opzionale)
3. Click **"Genera"**

**⚠️ IMPORTANTE**: 
- La chiave viene mostrata **una sola volta**
- Annotala in luogo sicuro
- Formato: `sk_live_xxxxxxxxxxxxxxxxxxxxx`

**Revoca API Key**:
1. Trova key nella tabella
2. Click **"Revoca"**
3. Conferma
4. ✅ Key invalidata immediatamente

**Monitoraggio Usage**:
- Tabella mostra: Richieste totali, Ultima richiesta, Errori
- Click su key per dettagli statistiche

### 6.3 Security Headers & CSP

**Verifica Configurazione**:
1. DevTools → System Check → Tab "Security"
2. Verifica:
   - ✅ `Content-Security-Policy` attivo
   - ✅ `X-Frame-Options: DENY`
   - ✅ `X-Content-Type-Options: nosniff`
   - ✅ `Strict-Transport-Security` (HSTS)
   - ✅ `Session Cookie: HttpOnly, Secure, SameSite=Strict`

**Modifica CSP** (avanzato):
1. File: `src/Middleware/SecurityHeadersMiddleware.php`
2. Linea `$csp = "..."`
3. Aggiungi domini trusted
4. Riavvia server

---

## 🛠️ DEVTOOLS E DEBUG {#devtools}

### 7.1 Accesso DevTools Dashboard

**Menu**: Icona **⚙️ DevTools** nella navbar

**Sezioni Disponibili**:
- 🗄️ Database Inspector
- 📁 File System
- 📊 Audit Log Viewer
- 🔒 Security Panel
- 🩺 System Health

### 7.2 Database Inspector

**Funzionalità**:
- **Query Executor**: Esegui query SQL dirette
- **Table Browser**: Naviga tabelle e record
- **Schema Inspector**: Visualizza struttura DB

**Esempio Query Utili**:

```sql
-- Trova soci creati oggi
SELECT * FROM soci 
WHERE DATE(created_at) = CURDATE() 
AND deleted_at IS NULL;

-- Conta morosi per anno
SELECT YEAR(data_nascita) as anno, COUNT(*) as morosi
FROM soci WHERE ...;

-- Verifica integrità
CHECK TABLE soci;
```

**⚠️ ATTENZIONE**: 
- Query di modifica (UPDATE, DELETE) richiedono conferma
- Backup automatico prima di operazioni critiche

### 7.3 File System Browser

**Navigazione Storage**:
1. DevTools → File System
2. Naviga cartelle: `uploads/`, `backups/`, `logs/`
3. Azioni:
   - **Download**: Click su file
   - **Delete**: Rimozione file (soft delete prima 30gg)
   - **View**: Anteprima (PDF, immagini)

**Sicurezza**:
- ❌ Path traversal bloccato (no `../`)
- ✅ Solo directory whitelisted
- ✅ Audit log per ogni accesso

### 7.4 System Health Monitor

**Dashboard Resilienza**:
- **Database**: Integrità tabelle, foreign key violations
- **Disk Space**: Utilizzo storage
- **Backups**: Ultimo backup, età, count
- **Logs**: Correlation ID presenti, file size
- **Sessions**: Cookie security config

**Alert Automatici**:
- 🔴 Critico: Disk space < 10%
- 🟡 Warning: Backup > 24h
- 🟢 OK: Tutto operativo

---

## 📈 REPORT E STATISTICHE {#report}

### 8.1 Dashboard Statistiche

**Menu**: `Intelligence → Statistiche`

**Metriche Disponibili**:
- **Totale Soci**: Conteggio complessivo
- **Attivi**: Con stato "ATTIVO"
- **Morosi**: Senza pagamento anno corrente
- **Percentuali**: Attivi, Paganti, Morosi

**Grafici**:
- **📊 Trend Iscritti**: Grafico a linee per mese
- **🥧 Demografica**: Distribuzione per fasce età
  - Under 18
  - 18-30
  - 31-50
  - 51-70
  - Over 70

### 8.2 Export Report

**Formati Disponibili**:
- **PDF**: Report stampabile con grafici
- **Excel (.xlsx)**: Data per analisi
- **CSV**: Importabile in altri sistemi

**Procedura**:
1. Statistiche → Click **"Esporta Report"**
2. Scegli formato
3. Scegli scope:
   - Tutti i soci
   - Solo attivi
   - Solo morosi
   - Custom query
4. **Download**

### 8.3 Report GDPR

**Export Dati Personali di un Socio**:
1. DevTools → Security Panel → Tab "GDPR"
2. Inserisci **Codice Fiscale**
3. Click **"Esporta Dati GDPR"**
4. Download JSON completo con:
   - Dati anagrafici
   - Documenti
   - Consensi
   - Storia modifiche (audit)

**Utilizzo**: Richieste ai sensi Art. 15 GDPR (diritto di accesso)

---

## 💾 BACKUP E MANUTENZIONE {#backup}

### 9.1 Sistema Backup Automatico

**Configurazione Default**:
- **Frequenza**: Ogni 24h (cron job)
- **Retention**: 30 giorni
- **Location**: `storage/backups/`
- **Rotazione**: Automatica (elimina backup > 30gg)

**Verifica Backup**:
1. DevTools → System Health → Tab "Backups"
2. Verifica:
   - ✅ Ultimo backup < 24h
   - ✅ Count backups >= 7 (1 settimana)
   - ✅ Dimensione file ragionevole

### 9.2 Backup Manuale

**Procedura**:
1. SSH al server
2. Esegui: `php bin/maintenance/backup.php`
3. Output: `database_backup_YYYYMMDD_HHMMSS.sql`
4. Download via FTP/SFTP

**Backup Completo (DB + Files)**:
```bash
# Database
php bin/maintenance/backup.php

# Files (storage)
tar -czf storage_backup.tar.gz storage/

# Config
tar -czf config_backup.tar.gz config/ .env
```

### 9.3 Restore da Backup

**⚠️ PROCEDURA CRITICA - Solo in caso emergenza**

**Prerequisiti**:
1. Accesso SSH al server
2. Backup file `.sql` valido
3. Permessi amministrativi DB

**Step**:
```bash
# 1. Stop dell'applicazione
sudo systemctl stop php-fpm

# 2. Backup DB corrente (safety)
mysqldump -u root -p fratellanza_db > pre_restore_backup.sql

# 3. Drop e ricrea DB
mysql -u root -p
DROP DATABASE fratellanza_db;
CREATE DATABASE fratellanza_db CHARACTER SET utf8mb4;
exit

# 4. Restore
mysql -u root -p fratellanza_db < database_backup_20260106.sql

# 5. Verifica
mysql -u root -p fratellanza_db -e "SELECT COUNT(*) FROM soci;"

# 6. Restart
sudo systemctl start php-fpm
```

### 9.4 Manutenzione Pianificata

**Operazioni Mensili**:
- ✅ Verifica integrità DB: `CHECK TABLE`
- ✅ Ottimizza tabelle: `OPTIMIZE TABLE soci, documenti`
- ✅ Pulizia logs vecchi (> 90gg)
- ✅ Test restore backup

**Operazioni Annuali**:
- ✅ Aggiornamento dipendenze: `composer update`
- ✅ Security audit: `composer audit`
- ✅ Rinnovo certificati SSL
- ✅ Review permessi utenti

---

## 🔌 API MANAGEMENT {#api}

### 10.1 API REST v1

**Endpoint Base**: `https://tuodominio.it/api/v1/`

**Autenticazione**:
```http
GET /api/v1/soci
Authorization: Bearer sk_live_xxxxxxxxxxxxx
```

**Endpoint Disponibili**:
- `GET /api/v1/soci` - Lista paginata soci
  - Query params: `page`, `perPage`
- `GET /api/v1/soci/{cf}` - Dettaglio socio
- `POST /api/v1/soci` - Crea socio
- `PUT /api/v1/soci/{cf}` - Aggiorna socio
- `DELETE /api/v1/soci/{cf}` - Elimina (soft)

**Documentazione Completa**: `Documentazione/Manuali/API_REFERENCE.md`

### 10.2 API GraphQL

**Endpoint**: `https://tuodominio.it/api/graphql`

**Query Esempio**:
```graphql
query {
  socio(codiceFiscale: "RSSMRA80A01H501Z") {
    nome
    cognome
    email
    stato
    verificaMorosita
  }
}
```

**Mutation Esempio**:
```graphql
mutation {
  createSocio(input: {
    codiceFiscale: "RSSMRA80A01H501Z"
    nome: "Mario"
    cognome: "Rossi"
    dataNascita: "1980-01-01"
  }) {
    codiceFiscale
    matricola
  }
}
```

**GraphiQL Explorer**: Disponibile in ambiente development

### 10.3 Rate Limiting

**Limiti Default**:
- **Per IP**: 100 req/min (globale)
- **Per API Key**: Configurabile (default 60 req/min)

**Header Response**:
```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1609459200
```

**Gestione 429 Too Many Requests**:
1. Client deve implementare exponential backoff
2. Header `Retry-After` indica secondi attesa

---

## 🔧 RISOLUZIONE PROBLEMI {#troubleshooting}

### 11.1 Problemi Comuni

#### **Problema: Impossibile accedere dopo 2FA**

**Sintomi**: Codice OTP rifiutato
**Causa**: Clock del server/device non sincronizzato

**Soluzione**:
1. Verifica orario device (deve essere sincronizzato con NTP)
2. Se admin: SSH al server → `ntpdate -u pool.ntp.org`
3. Se persistente: Usa codice backup salvato al setup

#### **Problema: Upload documento fallisce**

**Sintomi**: Errore "File troppo grande" o timeout
**Causa**: Limite PHP `upload_max_filesize`

**Soluzione**:
1. SSH al server
2. Modifica `php.ini`:
   ```ini
   upload_max_filesize = 20M
   post_max_size = 25M
   max_execution_time = 300
   ```
3. Restart PHP-FPM: `sudo systemctl restart php-fpm`

#### **Problema: Soci non visibili in archivio**

**Sintomi**: Tabella vuota o record mancanti
**Causa**: Soft delete attivo, filtro stato

**Soluzione**:
1. Verifica filtri applicati (reset all)
2. DevTools → Database Inspector
3. Query: `SELECT * FROM soci WHERE deleted_at IS NOT NULL`
4. Se trovati record: sono soft-deleted, recuperabili

#### **Problema: Grafico statistiche vuoto**

**Sintomi**: Dashboard senza dati
**Causa**: Cache Redis stale, query lenta

**Soluzione**:
1. Forza refresh: Ctrl+F5 o Clear cache browser
2. Se persiste: DevTools → System Check → Redis
3. Se Redis down: Restart `sudo systemctl restart redis`
4. Query manuale: `SELECT COUNT(*) FROM soci WHERE deleted_at IS NULL`

### 11.2 Log di Sistema

**Percorsi Log**:
- **Application**: `logs/app.log`
- **Errori PHP**: `logs/error.log`
- **Audit Trail**: Database table `audit_logs`
- **Sentry** (se configurato): Dashboard cloud

**Analisi Log**:
```bash
# Ultimi 100 errori
tail -n 100 logs/error.log

# Filtra per gravità
grep "CRITICAL" logs/app.log

# Conta errori per tipo
grep -o "Exception: [^:]*" logs/error.log | sort | uniq -c

# Ricerca per Correlation ID
grep "request_id:abc123" logs/app.log
```

### 11.3 Contatti Supporto

**Livello 1 - Operatori**:
- Email: support@associazione.it
- Tel: +39 xxx xxx xxxx
- Orario: Lun-Ven 9-18

**Livello 2 - Amministratori**:
- Email tecnico: admin@associazione.it
- Slack: #tech-support (se configurato)

**Livello 3 - Sviluppatore**:
- Email: dev@progetto.it
- Solo per issues critici (down time, data loss)

---

## 📚 RISORSE AGGIUNTIVE

- **📖 Documentazione Completa**: `Documentazione/` folder
- **🔌 API Reference**: `Documentazione/Manuali/API_REFERENCE.md`
- **🐳 Docker Guide**: `Documentazione/Manuali/GUIDA_DOCKER.md`
- **☁️ Deploy Railway**: `Documentazione/Manuali/GUIDA_RAILWAY.md`
- **🔧 Debug Tools**: `Documentazione/Manuali/GUIDA_DEBUG_TOOLS.md`

---

## ✅ CHECKLIST AMMINISTRATORE

**Operazioni Giornaliere**:
- [ ] Verifica System Health (alert rossi/gialli)
- [ ] Review Audit Log per azioni sospette
- [ ] Check backup notturno eseguito

**Operazioni Settimanali**:
- [ ] Revisione nuovi soci creati
- [ ] Verifica lista morosi
- [ ] Export report statistiche
- [ ] Review API usage (rate limit violations)

**Operazioni Mensili**:
- [ ] Pulizia documenti obsoleti (se policy)
- [ ] Aggiornamento permessi utenti
- [ ] Test restore backup
- [ ] Security audit (composer audit, npm audit)

**Operazioni Trimestrali**:
- [ ] Review completa archivio (data quality)
- [ ] Formazione nuovi operatori
- [ ] Aggiornamento documentazione
- [ ] Pianificazione miglioramenti

---

**Fine Manuale Amministratore**  
*Versione 2.0 - Gennaio 2026*  
*Per assistenza: admin@associazione.it*
