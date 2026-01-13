# DOCUMENTAZIONE COMPLETA PROGETTO
## Digitalizzazione e Dematerializzazione Archivio Soci - MCAG di Firenze

**Data:** 21 Dicembre 2025  
**Versione Software:** 1.3.1 (Mission-Critical Enterprise)  
**Sviluppo A cura di:** Soobadur Mohammad Ajmeer - Tecnico Informatico

---

# PARTE 1: RELAZIONE DI PROGETTO (Proposal)

### 1. Executive Summary
Il presente documento illustra l'elevazione del sistema allo standard **Mission-Critical**. Oltre alla transizione digitale, il sistema ora garantisce l'integrità atomica dei dati, la resilienza operativa tramite disaster recovery automatizzato e la tracciabilità end-to-end tramite Correlation IDs.

### 2. Obiettivi TO-BE (v1.3.1)
Il sistema garantisce:
- **Zero Data Loss**: Tramite transazioni PDO atomiche.
- **Ritorno all'Operatività**: Tramite Backup Service rotativo.
- **Forensics Avanzata**: Tramite tracciamento di ogni richiesta con Request ID univoci.

---

# PARTE 2: MANIUAL TECNICO E ANALISI DEL CODICE SORGENTE

## 1. Architettura Software Mission-Critical
L'architettura Layered è stata potenziata con pattern di resilienza e osservabilità.

### Stack Tecnologico (Aggiornato v1.3.1)
- **Persistence Layer**: SQLite 3 con **Foreign Keys attive** e **Transazioni Atomiche**.
- **Security Hardening**: Sessioni Strict, Storage Lockdown (.htaccess), 2FA e Rate Limiting.
- **Osservabilità**: Middleware di generazione **Request ID** e logging correlato.
- **Diagnostica**: Resilience Monitor e Advanced Log Analyzer.
- **Testing**: PestPHP 3 con **71 Test** (100% copertura casi critici).

## 2. Analisi Dettagliata dei Componenti

### A. Core di Resilienza (`src/Debug/` & `src/Service/`)
- **`BackupService.php`**: Gestisce la copia a caldo del database con logica di retention (14 giorni).
- **`ResilienceMonitor.php`**: Esegue scansioni proattive sull'integrità fisica del database e sullo stato dei servizi di sicurezza.
- **`LogAnalyzer.php`**: Permette l'estrazione di tracce log filtrate per Request ID per incident response.

### B. Persistenza Transazionale (`src/InfrastrutturaIT/Persistence/`)
- **`SQLiteSocioRepository.php`**: Ora implementa il pattern di transazione atomica. Il salvataggio di un socio e dei suoi allegati avviene in un'unica operazione "tutto-o-niente", prevenendo stati inconsistenti del database in caso di crash mid-request.

### C. Security & Observability Layer
- **`RequestIdMiddleware.php`**: Assegna un ID univoco (`X-Request-ID`) a ogni transazione HTTP.
- **`SessionManager.php`**: Garantisce la rigenerazione sicura dell'ID di sessione post-login per prevenire Session Fixation.
- **`AuditTrail`**: Evoluto in un sistema database-backed con pseudonimizzazione integrata.

---

# PARTE 3: ANALISI SWOT

### Punti di Forza (Strengths)
1.  **Affidabilità Mission-Critical**: Integrità dei dati garantita a livello transazionale.
2.  **Tracciabilità End-to-End**: Debugging facilitato dai Correlation IDs.
3.  **Resilienza Testata**: Suite di 71 test che validano anche scenari di crash e rollback.

---

# PARTE 4: GERARCHIA COMPLETA DEL PROGETTO (v1.3.1)

```text
/
├── bin/
│   ├── debug_console/                                  # CLI Incident Response Hub
│   ├── backup.php                                      # Disaster Recovery Runner
│   └── check_system.php                                # Proactive Health Check
│
├── src/Debug/                                          # Resilience & Observability
│   ├── ResilienceMonitor.php                           # Health Engine
│   └── LogAnalyzer.php                                 # Trace Engine
│
├── storage/uploads/                                    # Cloud/Local Storage Protettore
│   └── .htaccess                                       # Filesystem Lockdown
│
├── config/                                             # Modularity & DI
│   ├── container.php                                   # Dependency Injection (v1.3.1)
│   └── routes.php                                      # Named Routes Architecture
```

---
*Digitalizzazione a cura di Soobadur Mohammad Ajmeer - Tecnico Informatico*

