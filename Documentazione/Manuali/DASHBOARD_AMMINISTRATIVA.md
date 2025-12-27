# Dashboard Amministrativa (Mission Control Center)

**Edizione v2.0 Enterprise - Strumenti per SysAdmin**

Il sistema dispone di due livelli di controllo: **Web Dashboard** (per monitoraggio quotidiano) e **CLI Toolbox** (per diagnostica profonda e disaster recovery).

---

## 1. 🖥️ Web Dashboard (`/devtools`)

Accessibile tramite il menu "Strumenti > Mission Control" (solo Amministratori).

### 📊 Resilience Monitor
- **Health Score**: Punteggio da 0 a 100% basato sullo stato del sistema.
- **Security Hub**:
    - Monitoraggio tentativi di accesso falliti.
    - Stato rotazione 2FA utenti.
- **Database Metrics**: Dimensione DB, tempi di risposta query e stato cache.

### 📜 Log & Audit Explorer
Strumento visuale per ispezionare il registro delle operazioni:
- **Audit Trail**: Chi ha fatto cosa e quando (creazione, modifica, cancellazione).
- **System Logs**: Errori tecnici filtrabili per livello (ERROR, WARNING, INFO).

---

## 2. 🛠️ CLI Toolbox (`bin/tools`)

In caso di malfunzionamento dell'interfaccia web, usa la riga di comando sul server.

### Diagnostica
```powershell
# Check completo dello stato di salute
php bin/tools/health_check.php

# Audit di Sicurezza (privilegi file, config php)
php bin/tools/security_audit_cli.php

# Test Invio Email (SMTP)
php bin/tools/test_smtp.php
```

### Manutenzione Database
```powershell
# Riparazione integrità Relazionale
php bin/maintenance/check_integrity.php

# Verifica Backup
php bin/maintenance/backup_verify.php
```

---

## 3. 🚨 Procedure di Emergenza

### Backup Manuale
Se devi eseguire un aggiornamento o una migrazione:
```powershell
php bin/maintenance/backup_daily.php
```
I file verranno salvati in `storage/backups/`.

### Reset Chiavi Sicurezza
Se sospetti una compromissione delle chiavi:
```powershell
php bin/maintenance/regenerate_key_clean.php
```
*⚠️ Attenzione: Questo disconnetterà tutti gli utenti attualmente loggati.*

---
*Manuale Tecnico - Fratellanza Militare IT Dept.*
