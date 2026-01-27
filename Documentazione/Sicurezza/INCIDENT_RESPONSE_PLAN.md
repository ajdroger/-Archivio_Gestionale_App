# 🚨 INCIDENT RESPONSE PLAN MCAG
## Piano di Risposta agli Incidenti di Sicurezza

**Versione**: 1.0  
**Data**: 27 Gennaio 2026  
**Sistema**: MCAG v8.3.0+  
**Tipo**: Documento Security Critical

---

## 📋 INDICE

1. [Overview & Obiettivi](#1-overview--obiettivi)
2. [Incident Classification](#2-incident-classification)
3. [Response Team](#3-response-team)
4. [Detection & Reporting](#4-detection--reporting)
5. [Response Procedures](#5-response-procedures)
6. [Communication Plan](#6-communication-plan)
7. [Escalation Matrix](#7-escalation-matrix)
8. [Forensics & Investigation](#8-forensics--investigation)
9. [Post-Incident Review](#9-post-incident-review)
10. [Templates & Checklists](#10-templates--checklists)

---

## 1. OVERVIEW & OBIETTIVI

### 1.1 Scopo

Questo piano definisce le procedure operative per **rilevare, rispondere e recuperare** da incidenti di sicurezza che potrebbero compromettere:
- Confidenzialità dati clienti/soci
- Integrità del sistema
- Disponibilità servizi
- Compliance normativa (GDPR)

### 1.2 Obiettivi

- **Detection Time**: < 15 minuti dall'evento
- **Response Time**: < 30 minuti dalla detection
- **Containment Time**: < 2 ore dalla detection
- **Recovery Time**: < 8 ore per incidenti P1/P2

### 1.3 Scope

**In Scope**:
- Violazioni dati (data breach)
- Accessi non autorizzati
- Malware/Ransomware
- DDoS attacks
- SQL Injection attempts
- Insider threats
- Physical security breaches

**Out of Scope**:
- Downtime pianificato per manutenzione
- Bug applicativi non security-related
- Performance degradation non malevolo

---

## 2. INCIDENT CLASSIFICATION

### 2.1 Severity Levels

| Priority | Descrizione | Impatto | Response Time | Esempi |
|----------|-------------|---------|---------------|--------|
| **P0 - CRITICAL** | Sistema compromesso, data breach massivo | Totale perdita confidenzialità/integrità | Immediato (< 15 min) | Ransomware attivo, database leak pubblico, root compromise |
| **P1 - HIGH** | Compromissione parziale, rischio imminente | Alto, possibile escalation a P0 | < 30 minuti | Malware rilevato, SQLi successful, unauthorized admin access |
| **P2 - MEDIUM** | Vulnerabilità sfruttata, incident contenuto | Moderato, impatto limitato | < 2 ore | Brute force attempts, XSS exploit, DDoS mitigato |
| **P3 - LOW** | Tentativo fallito, anomalia rilevata | Basso, nessun impatto immediato | < 8 ore | Failed login spikes, port scanning, phishing email ricevuta |
| **P4 - INFO** | Evento sicurezza, no azione immediata | Nullo | < 24 ore | New CVE pubblicato per dependency, security advisory |

### 2.2 Incident Categories

**CAT-A: Data Breach**
- Unauthorized data access
- Data exfiltration
- Database exposure

**CAT-B: System Compromise**
- Server/container compromesso
- Privilege escalation
- Backdoor installation

**CAT-C: Malware**
- Virus/worm detection
- Ransomware encryption
- Trojan/spyware

**CAT-D: Network Attack**
- DDoS flood
- Man-in-the-middle
- DNS hijacking

**CAT-E: Application Attack**
- SQL Injection
- XSS/CSRF exploitation
- Authentication bypass

**CAT-F: Insider Threat**
- Malicious employee activity
- Data theft interno
- Sabotage

---

## 3. RESPONSE TEAM

### 3.1 Roles & Responsibilities

**Incident Commander (IC)** - Soobadur Mohammad Ajmeer:
- Decision authority finale
- Coordina response team
- Approva comunicazioni esterne
- Dichiara fine incident

**Technical Lead**:
- Analisi tecnica incident
- Implementa containment/eradication
- Coordina con DevOps/System Admins
- Forensics collection

**Communications Lead**:
- Redige comunicazioni interne/esterne
- Coordina con Legal/PR
- Notifiche clienti/stakeholder
- Media liaison

**Legal/Compliance**:
- GDPR breach notification (72h)
- Regulatory reporting
- Legal liability assessment
- Contract review

**DevOps/SysAdmin**:
- Logs collection
- System isolation
- Backup/restore operations
- Infrastructure changes

### 3.2 On-Call Rotation

**24/7 Coverage**:
- Primary On-Call: Incident Commander
- Secondary On-Call: Technical Lead
- Escalation: Communications + Legal (business hours)

**Contact Information**:
| Role | Primary | Phone | Email | Backup |
|------|---------|-------|-------|--------|
| IC | Ajmeer | +39-XXX-XXXXXXX | ajmeer@mcag.it | - |
| Tech Lead | TBD | +39-XXX-XXXXXXX | tech@mcag.it | DevOps |
| Comms | TBD | +39-XXX-XXXXXXX | comms@mcag.it | Marketing |
| Legal | Studio XYZ | +39-XXX-XXXXXXX | legal@mcag.it | - |

---

## 4. DETECTION & REPORTING

### 4.1 Detection Sources

**Automated Monitoring** (Sentry, Prometheus, SIEM):
- Failed login attempts > 50/5min
- Unusual database queries (volume, pattern)
- Unauthorized file modifications
- Network traffic anomalies
- Malware signatures (ClamAV)
- IDS/IPS alerts

**Manual Detection**:
- User report (sospetto phishing, anomalie)
- Security audit finding
- Vendor notification (CVE per dipendenza)
- External report (security researcher)

### 4.2 Reporting Channels

**Internal**:
- Email: security@mcag.it (monitored 24/7)
- Slack: #security-incidents (auto-page on-call)
- Phone Hotline: +39-XXX-SECURITY
- Web Form: https://mcag.it/report-security

**External** (anonymo, bug bounty):
- HackerOne: https://hackerone.com/mcag
- Email: security-external@mcag.it (PGP encrypted)

### 4.3 Initial Triage

**First Responder Actions** (entro 15 minuti):

1. **Assess Severity**:
   - Quale dato è compromesso?
   - Quanti utenti impattati?
   - Sistema ancora sotto attacco?
   - Classification: P0-P4?

2. **Create Incident Ticket**:
   - JIRA Security Project
   - ID univoco: INC-YYYY-NNNN
   - Tag: severity, category, status

3. **Notify Team**:
   - Page Incident Commander
   - Alert Technical Lead
   - Post in #security-incidents

4. **Preserve Evidence**:
   - Snapshot logs current state
   - Capture network traffic se possibile
   - Screenshot sistema colpito
   - NO modifiche sistema prima forensics

---

## 5. RESPONSE PROCEDURES

### 5.1 Standard Response Flow

```
Detection → Triage → Containment → Eradication → Recovery → Review
    ↓         ↓          ↓              ↓            ↓         ↓
 < 15min  < 30min    < 2h           < 8h         < 24h    < 7d
```

### 5.2 Phase 1: Containment (< 2 ore)

**Obiettivo**: Limitare il danno e prevenire escalation

**Actions**:

**A. Network Isolation** (se compromissione server):
```bash
# Isola server da rete
iptables -P INPUT DROP
iptables -P OUTPUT DROP
# Permetti solo accesso SSH da jump host
iptables -A INPUT -s <JUMP_HOST_IP> -p tcp --dport 22 -j ACCEPT
```

**B. Account Lockdown** (se compromissione credential):
```bash
# Disable compromised account
php bin/console user:disable --email=compromised@example.com

# Force logout all sessions
php bin/console session:flush-all

# Rotate API keys/tokens
php bin/console api:rotate-keys --force
```

**C. Database Query Kill** (se SQL Injection attivo):
```sql
-- Identifica e killa query malevole
SHOW FULL PROCESSLIST;
KILL <process_id>;

-- Revoca permessi sospetti
REVOKE ALL PRIVILEGES ON mcag.* FROM 'compromised_user'@'%';
```

**D. Traffic Blocking** (se DDoS/bruteforce):
```bash
# Blocca IP attaccante
iptables -A INPUT -s <ATTACKER_IP> -j DROP

# Rate limiting Nginx
limit_req_zone $binary_remote_addr zone=ddos:10m rate=10r/s;
```

### 5.3 Phase 2: Eradication (< 8 ore)

**Obiettivo**: Rimuovere la causa root dell'incident

**Actions**:

**A. Malware Removal**:
```bash
# Scan completo
clamscan -r /var/www/mcag --infected --remove

# Verifica integrità file (confronto con repo git)
git diff --stat

# Ripristina file compromessi
git checkout HEAD -- <compromised_files>
```

**B. Vulnerability Patching**:
```bash
# Update dipendenze vulnerabili
composer update <vulnerable/package> 
npm update <vulnerable-package>

# Apply security patch
git apply security-patch-CVE-2026-XXXX.patch

# Redeploy applicazione
./deploy.sh
```

**C. Credential Rotation**:
```bash
# Rotate database passwords
ALTER USER 'mcag'@'localhost' IDENTIFIED BY '<NEW_STRONG_PASSWORD>';

# Rotate encryption keys
php bin/console key:rotate --backup-old

# Rotate JWT secrets
php bin/console jwt:generate-secret
```

**D. Access Revocation**:
```bash
# Revoca accessi compromessi
php bin/console access:revoke --user=<compromised_user>

# Audit user permissions
php bin/console audit:permissions --report
```

### 5.4 Phase 3: Recovery (< 24 ore)

**Obiettivo**: Ripristinare servizio normale e validare security

**Actions**:

**A. Service Restoration**:
```bash
# Riporta online servizi isolati
php bin/console up

# Restore da backup se necessario
php bin/console backup:restore --tag=pre-incident-<timestamp>

# Verifica integrità dati
php bin/console db:verify-integrity
```

**B. Monitoring Enhancement**:
- Deploy additional logging
- Increase alert sensitivity temporary
- Continuous threat hunting (48-72h post-incident)

**C. Validation**:
- Penetration test mirato su vulnerability
- Security scan completo (Nessus, Burp Suite)
- Code review security-focused

---

## 6. COMMUNICATION PLAN

### 6.1 Internal Communication

**During Incident**:
- Slack #security-incidents: Real-time updates ogni 30 minuti
- Email stakeholder: Update ogni 2 ore (exec team)
- Status page (interno): https://status.internal.mcag.it

**Post-Incident**:
- All-hands meeting (entro 48h)
- Written debrief circulated team (entro 7 giorni)

### 6.2 External Communication

**Customer Notification** (se data breach):

**Timeline**:
- P0/P1 con data breach: Entro 24 ore
- P2 con data exposure limitata: Entro 72 ore

**Template Email**:
```
Subject: Important Security Notice - MCAG System

Gentile Cliente,

Vi contattiamo per informarvi di un incident di sicurezza che ha
potenzialmente impattato i vostri dati.

COSA È SUCCESSO:
[Descrizione concisa incident]

DATI POTENZIALMENTE IMPATTATI:
[Lista categorie dati]

COSA ABBIAMO FATTO:
[Azioni containment/eradication]

COSA POTETE FARE:
[Raccomandazioni utenti: reset password, monitor account, etc.]

Per ulteriori informazioni: security@mcag.it

Cordiali saluti,
MCAG Security Team
```

**Regulatory Notification** (GDPR):
- Garante Privacy: Entro 72 ore da discovery se data breach
- Format: https://www.garanteprivacy.it/temi/databreach

### 6.3 Media Relations

**Spokesperson**: CEO/Communications Lead only  
**Message**: Pre-approved talking points  
**Channel**: Press release solo se richiesto, no proactive disclosure

---

## 7. ESCALATION MATRIX

### 7.1 Escalation Triggers

**To IC**: Sempre per P0/P1, opzionale per P2+  
**To Legal**: Sempre se potenziale data breach GDPR  
**To CEO**: Sempre per P0, P1 se >100 clienti impattati  
**To Law Enforcement**: P0 con evidenza crimine (ransomware, theft)

### 7.2 Escalation Contacts

| Level | Role | Phone | Escalation Criteria |
|-------|------|-------|---------------------|
| L1 | On-Call Engineer | +39-XXX-1111 | All incidents |
| L2 | Technical Lead | +39-XXX-2222 | P0-P2 |
| L3 | Incident Commander | +39-XXX-3333 | P0-P1 |
| L4 | CEO | +39-XXX-4444 | P0 or >€100K damage |
| L5 | Law Enforcement | 112 / Polizia Postale | P0 criminal activity |

---

## 8. FORENSICS & INVESTIGATION

### 8.1 Evidence Collection

**Digital Evidence**:
- Server logs (access, error, audit): 90 giorni retention
- Database query logs: 30 giorni
- Network traffic captures (pcap): 7 giorni rolling
- Application logs (Sentry): 180 giorni
- Backup snapshots: 1 anno

**Chain of Custody**:
1. Timestamp collection
2. Hash integrity (SHA-256)
3. Secure storage (encrypted, access-controlled)
4. Document custodian transfer

### 8.2 Root Cause Analysis

**5 Whys Method**:
```
Incident: Unauthorized admin access

Why 1: Password compromesso
Why 2: Password weak (8 char, no symbols)
Why 3: Policy non enforce complexity
Why 4: Sistema legacy no password validation
Why 5: Technical debt prioritized over security
```

**Corrective Actions**:
- [ ] Implement strong password policy enforcement
- [ ] Deploy password manager enterprise
- [ ] Mandatory security training tutti dev

---

## 9. POST-INCIDENT REVIEW

### 9.1 Timeline Reconstruction

**Incident Timeline Template**:

| Timestamp | Event | Source | Action | Outcome |
|-----------|-------|--------|--------|---------|
| 2026-01-15 14:23 | Suspicious login detected | Sentry Alert | On-call paged | Acknowledged 14:25 |
| 2026-01-15 14:30 | SQLi confirmed | Manual inspection | DB isolation | Traffic blocked 14:35 |
| ... | ... | ... | ... | ... |

### 9.2 Lessons Learned

**What Went Well**:
- Detection rapida (< 10 min)
- Containment efficace
- Team collaboration

**What Went Wrong**:
- Vulnerability nota ma non patchata
- Backup procedure incompleta
- Communication esterna delayed

**Action Items**:
- [ ] Patch management processo migliorato
- [ ] Backup automation verificata settimanalmente
- [ ] Communication templates pre-approved

### 9.3 Metrics

**Track per ogni incident**:
- Time to Detection (TTD)
- Time to Containment (TTC)
- Time to Recovery (TTR)
- Customer Impact (# users, data volume)
- Financial Impact (€ perso/speso)

---

## 10. TEMPLATES & CHECKLISTS

### 10.1 Incident Response Checklist

**P0/P1 Checklist**:

**Detection Phase**:
- [ ] Incident ticket creato (INC-YYYY-NNNN)
- [ ] Severity classificata
- [ ] IC notificato
- [ ] War room aperta (Slack/Zoom)

**Containment Phase**:
- [ ] Compromised system isolated
- [ ] Affected accounts disabled
- [ ] Evidence preserved (logs, snapshots)
- [ ] Backup verified

**Eradication Phase**:
- [ ] Root cause identified
- [ ] Vulnerability patched
- [ ] Malware removed
- [ ] Credentials rotated

**Recovery Phase**:
- [ ] Service restored
- [ ] Integrity validated
- [ ] Monitoring enhanced
- [ ] Penetration test executed

**Communication Phase**:
- [ ] Internal team updated
- [ ] Customers notified (if applicable)
- [ ] Regulatory notification (if GDPR breach)
- [ ] Media statement (if required)

**Post-Incident**:
- [ ] Timeline documented
- [ ] RCA completed
- [ ] Lessons learned session
- [ ] Action items tracked (JIRA)
- [ ] Incident report archived

### 10.2 Communication Templates

#### Template: Internal Incident Notification

```
Subject: SECURITY INCIDENT - [P0/P1/P2] - [INC-2026-0042]

Team,

Abbiamo rilevato un incident di sicurezza [CATEGORY] con severità [PX].

STATUS: [DETECTING / CONTAINING / ERADICATING / RECOVERING]

IMPACT: 
- Sistemi: [list]
- Utenti: [number]
- Dati: [types]

NEXT ACTIONS:
- [Immediate next steps]

WAR ROOM: slack.com/archives/security-incidents

Updates ogni 30 minuti.

Incident Commander
```

#### Template: Customer Data Breach Notification

```
Subject: Importante Notifica Sicurezza - MCAG

Gentile [CUSTOMER_NAME],

[REST OF TEMPLATE AS SHOWN IN SECTION 6.2]
```

---

## CONCLUSIONE

Un Incident Response Plan efficace è la **differenza tra un incident contenuto e un disaster**. MCAG implementa procedure enterprise-grade per garantire:

- ⏱️ **Detection rapida** (< 15 minuti)
- 🛡️ **Containment efficace** (< 2 ore)
- 🔧 **Recovery veloce** (< 24 ore)
- 📢 **Communication trasparente** (GDPR compliant)

**Il piano deve essere testato regolarmente** tramite tabletop exercises e simulazioni incident.

---

**© 2026 Soobadur Mohammad Ajmeer - All Rights Reserved**  
**MCAG Incident Response Plan**  
**Versione**: 1.0 Confidential  
**Data**: 27 Gennaio 2026  
**Review Frequency**: Trimestrale o post-incident major
