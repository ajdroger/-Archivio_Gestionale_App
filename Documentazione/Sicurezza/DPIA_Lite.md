# DPIA - Valutazione d'Impatto sulla Protezione dei Dati (Lite)

**Progetto**: MCAG - Archivio Digitale Soci
**Data**: Gennaio 2026
**Stato**: BOZZA PRELIMINARE

## 1. Descrizione del Trattamento
L'applicazione gestisce un archivio digitale dei soci dell'associazione "MCAG".
**Dati Trattati**:
- Anagrafica (Nome, Cognome, CF, Indirizzo)
- Contatti (Email, Telefono)
- Documenti scansionati (PDF)

## 2. Necessità e Proporzionalità
Il trattamento è necessario per la gestione statutaria dell'associazione. I dati raccolti sono minimizzati al solo scopo gestionale e amministrativo.

## 3. Valutazione dei Rischi
| Rischio | Impatto | Probabilità | Misure di Mitigazione |
| :--- | :--- | :--- | :--- |
| Accesso non autorizzato | Alto | Bassa | 2FA, RBAC, Logging accessi |
| Perdita dati (Disaster) | Medio | Bassa | Backup giornalieri, Offsite |
| Furto credenziali | Alto | Media | Rate Limiting, Hashing sicuro, 2FA |
| SQL Injection | Alto | Bassissima | Prepared Statements (Audit 100%) |

## 4. Misure di Sicurezza Implementate
- **Crittografia**: HTTPS (HSTS), Crittografia colonne PII (CF).
- **Controllo Accessi**: Autenticazione a 2 fattori obbligatoria per admin.
- **Audit**: Log completi di accesso e modifica (AuditTrail).
- **Network**: WAF (Cloudflare ready), Fail2Ban.

## 5. Conclusioni
Il rischio residuo è considerato **ACCETTABILE** date le misure tecniche e organizzative implementate.

