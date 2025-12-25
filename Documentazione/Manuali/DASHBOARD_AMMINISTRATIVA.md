# Dashboard Amministrativa - Mission-Critical Ops Center

Il sistema dispone di un hub di controllo avanzato per la manutenzione e il monitoraggio proattivo della resilienza (v1.3.1).

## 📍 Strumenti di Controllo

### 1. Developer Dashboard (Web)
**Accesso**: `/devtools` (Riservato Admin)
La dashboard web integra il **Resilience Monitor** per una visione istantanea della salute del sistema:
- **Database Status**: Verifica integrità fisica e vincoli relazionali.
- **Backup State**: Monitoraggio della freschezza dei backup (Ultimo backup < 24h).
- **Log Observer**: Anteprima degli ultimi eventi critici nel sistema.
- **Quick Backup**: Pulsante per l'esecuzione istantanea del backup del database.

### 2. Log Trace Explorer (Web)
Integrato nei DevTools, permette di tracciare la "storia" di una richiesta tramite il **Correlation ID**:
- Inserendo un `Request ID` (es. `676...`), il sistema estrae tutti i log (App e Audit) correlati a quell'evento.
- Fondamentale per il debugging in tempo reale e la forensics post-incidente.

### 3. Mission-Critical Console (CLI)
**Comando**: `php bin/debug_console/console.php`
Un terminale interattivo per sysadmin progettato per operare anche in caso di down dell'interfaccia web:
- `health`: Report dettagliato della resilienza.
- `trace <ID>`: Tracciamento log CLI.
- `backup`: Gestione manuale della rotazione backup.

## 🚀 Logica di Resilienza

### Integrità Dati
Il sistema esegue controlli **PRAGMA** proattivi. In caso di corruzione del file database, il monitor segnalerà immediatamente lo stato `FAIL` nella dashboard.

### Disaster Recovery
Il `BackupService` salva copie incrementali in `storage/backups/`. La console amministrativa permette di verificare la presenza di almeno 14 giorni di storico.

---
## 🛡️ Accesso & Sicurezza
L'accesso agli strumenti amministrativi è protetto da:
- **AdminMiddleware**: Verifica dei permessi di ruolo.
- **Session Hardening**: Cookie `Strict` e `HttpOnly` per prevenire attacchi XSS alla dashboard.
- **Audit Logging**: Ogni azione compiuta in dashboard viene registrata con l'ID dell'amministratore.

---
*Ultimo aggiornamento: 21 Dicembre 2025 - Edizione Mission-Critical*
