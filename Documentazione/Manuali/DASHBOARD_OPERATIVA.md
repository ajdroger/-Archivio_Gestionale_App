# Dashboard Operativa - Quality & Automation Portal

Il portale della qualità è lo strumento dedicato alla verifica della stabilità del software e all'esecuzione degli script di automazione (v1.3.1 Mission-Critical).

## 📍 Localizzazione
Il file si trova in: `tests/test_dashboard.php` (accessibile via web).

## 🚀 Funzionalità Principali

### 🔍 1. Monitoraggio Test Suites (PestPHP)
Il sistema gestisce ora **71 test automatizzati** che coprono l'intero spettro operativo:
- **Unit & Feature**: Core logic e UI controller.
- **Resilience (New)**: Test di integrità transazionale e disaster recovery.
- **Security & Hardening**: Validazione 2FA, Rate Limiting e Session Security.
- **Observability**: Verifica della corretta generazione dei Correlation IDs.
- **GDPR Trace**: Validazione della pseudonimizzazione nei log correlati.

### 🛡️ 2. Mission-Critical Badge
Un indicatore visivo che certifica che il sistema ha superato non solo i test funzionali, ma anche quelli di resilienza e atomicità.

### ⚡ 3. Script di Automazione (bin/)
Accesso agli strumenti di gestione avanzata:
- **`simulation.php`**: Simulazione workflow end-to-end con tracciamento Request ID.
- **`backup.php`**: Innesco manuale della rotazione di sicurezza.
- **`check_system.php`**: Diagnostica proattiva con report di resilienza.

## 🎯 Scopo
Fornire agli operatori e agli sviluppatori la certezza matematica che il sistema sia in grado di resistere a guasti parziali e che ogni dato sia tracciabile e recuperabile.

---
*Ultimo aggiornamento: 21 Dicembre 2025 - Edizione Mission-Critical*
