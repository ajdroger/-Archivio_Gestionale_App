# 💀 DISASTER RECOVERY & DEAD MAN SWITCH PROTOCOL
**Level:** Omega | **Trigger:** Automated / Manual
**Maintainer:** Solo Developer (Automated System)

---

## 1. Scopo del Protocollo
Questo documento definisce le procedure AUTOMATIZZATE per garantire la sopravvivenza del progetto MCAG in caso di indisponibilità prolungata dello sviluppatore unico ("Bus Factor" event).

## 2. Il "Dead Man Switch" (Interruttore Uomo Morto)
Il sistema include uno script di monitoraggio (`bin/dead-man-switch.sh`) che verifica l'attività di commit su Git.

### Condizioni di Attivazione
- **Warning**: 15 giorni senza commit/login. Invia email di verifica allo sviluppatore.
- **Critical**: 30 giorni senza risposta. Attiva il protocollo **OMEGA**.

### Protocollo OMEGA (Automazione)
1.  **Backup Finale**: Dump completo del database e backup incrementale codificato.
2.  **Key Release**:
    *   Invia le chiavi crittografiche (AES-256) del repository a [Emergency Contact A] e [Emergency Contact B].
    *   Sblocca la documentazione "Admin Root" tecnica nascosta.
3.  **Server Failover**:
    *   Attiva una pagina di "Maintenance Mode" statica informativa sulla Landing Page.
    *   Sospende i cron jobs non essenziali per preservare lo stato.

## 3. Procedure di Disaster Recovery Tecnico

### Scenario A: Server Corrotto / Data Loss
**Tempo di Ripristino (RTO):** < 15 minuti.

1.  **Avvio Script One-Click**:
    ```bash
    ./INSTALL_ONE_CLICK.sh --restore-latest
    ```
2.  **Logica di Ripristino**:
    *   Lo script scarica l'ultimo backup cifrato da AWS S3 / Cloud Storage.
    *   Decripta utilizzando la chiave ambiente `MCAG_MASTER_KEY`.
    *   Ripristina Docker Containers e importa MySQL Dump.

### Scenario B: Attacco Ransomware
1.  **Isolamento**: Il sistema di monitoraggio rileva I/O anomalo e spegne i container Docker.
2.  **Rebuild**:
    *   Non pagare mai.
    *   Wipe totale del server (`rm -rf /`).
    *   Reinstallazione OS pulito.
    *   Esecuzione `INSTALL_ONE_CLICK.sh`.
    *   Perdita dati massima (RPO): 24 ore (ultimo backup notturno).

## 4. Eredità Digitale (Knowledge Transfer)
In caso di attivazione del Dead Man Switch, il sistema genera un pacchetto ZIP contenente:
1.  Tutte le password (Bitwarden vault export cifrato).
2.  Il file `MASTER_PLAN_SWOT_EXECUTION.md` corrente.
3.  Manuali completi PDF.
4.  Contratti attivi e contatti clienti.

Questo pacchetto viene inviato via email criptata (ProtonMail) ai contatti di fiducia designati nel file `.env`.

---
**STATO PROTOCOLLO**: 🟢 ARMADO E ATTIVO
**ULTIMO CHECK**: (Automatico)
