# 🔥 DISASTER RECOVERY PLAN MCAG
## Piano di Recupero da Disastro - Business Continuity

**Versione**: 1.0  
**Data**: 27 Gennaio 2026  
**Sistema**: MCAG v8.3.0+  
**Tipo**: Documento Business Continuity Critical

---

## 📋 EXECUTIVE SUMMARY

Questo Disaster Recovery Plan (DRP) definisce le procedure per garantire la **continuità operativa** del sistema MCAG in caso di disastro catastrofico che renda non

 disponibili componenti critici.

**Obiettivi Recovery**:
- **RTO (Recovery Time Objective)**: < 4 ore
- **RPO (Recovery Point Objective)**: < 15 minuti
- **Data Loss Tollerato**: < 0.1% transazioni

**Copertura Scenari**:
- Datacenter failure totale
- Cyber attack devastante (ransomware)
- Disastro naturale (terremoto, alluvione, incendio)
- Errore umano catastrofico
- Infrastructure provider outage prolungato

---

## 1. RTO/RPO DEFINITIONS

### 1.1 Recovery Time Objective (RTO)

**RTO**: Tempo massimo accettabile tra disastro e ripristino servizio funzionante.

| Sistema | Tier | RTO Target | Justification |
|---------|------|------------|---------------|
| **DB Production** | Tier 0 | < 2 ore | Core business data, blocca tutto |
| **Application Servers** | Tier 0 | < 3 ore | Servizio inaccessibile senza |
| **Redis Cache** | Tier 1 | < 1 ora | Performance degradation tollerabile 4h |
| **File Storage** | Tier 1 | < 4 ore | Documenti accessibili via backup temporaneo |
| **Email Services** | Tier 2 | < 8 ore | Communication critical ma non blocca ops |
| **AI/RAG Services** | Tier 2 | < 24 ore | Feature non core, acceptable delay |

### 1.2 Recovery Point Objective (RPO)

**RPO**: Massima perdita dati accettabile (finestra temporale).

| Tipo Dato | RPO Target | Backup Frequency | Justification |
|-----------|------------|------------------|---------------|
| **Transazioni DB** | < 15 min | Continuous replication + Backup 15min | Zero data loss critico |
| **Documenti Uploaded** | < 1 ora | Hourly snapshot + sync S3 | Documenti recuperabili da utente |
| **Configurazioni Sistema** | < 24 ore | Daily backup | Change frequency bassa |
| **Logs** | < 4 ore | 4h rotation + archive | Analisi possibile con gap accettabile |
| **Code Repository** | < 1 min | Git push continuous | Versioning garantisce zero loss |

---

## 2. DISASTER SCENARIOS

### 2.1 Scenario 1: Datacenter Total Failure

**Trigger**: Datacenter primario offline > 30 minuti, non recuperabile < 4h

**Cause Possibili**:
- Fire/Flood devastante
- Power outage prolungato (>8h)
- Network backbone failure
- Terrorism/sabotage

**Impact**:
- **ALL services offline**
- €50K/ora di revenue loss
- Reputational damage critico

**Recovery Strategy**: Failover a datacenter secondario (geograficamente distante)

---

### 2.2 Scenario 2: Ransomware Attack

**Trigger**: Sistema crittografato da ransomware, backup recenti compromessi

**Cause**:
- Zero-day exploit
- Insider threat
- Phishing successful su admin

**Impact**:
- Data encrypted
- Backup recenti infected
- Richiesta riscatto (Bitcoin)

**Recovery Strategy**: Restore da backup immutabile offline (cold storage)

---

### 2.3 Scenario 3: Database Corruption/Loss

**Trigger**: Database corrotto irreparabilmente, no replica funzionante

**Cause**:
- Hardware failure MySQL master
- Malicious DELETE/DROP eseguito
- Corruption dopo crash non recoverable

**Impact**:
- Complete data unavailability
- Business halt totale

**Recovery Strategy**: Point-in-time restore da backup automatici

---

### 2.4 Scenario 4: Human Error Catastrophic

**Trigger**: Comandi errati causano perdita dati/servizi massiva

**Esempi**:
- `DROP DATABASE mcag_production;`
- `rm -rf /var/www/mcag`
- Deploy errato che corrompe dati

**Recovery Strategy**: Rollback database + Code restore + Testing

---

## 3. BACKUP STRATEGY

### 3.1 Database Backups

**Full Backup** (daily, 02:00 AM UTC):
```bash
#!/bin/bash
# backup-db-full.sh

BACKUP_DIR="/backups/mysql/full"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="mcag_full_${TIMESTAMP}.sql.gz"

# MySQL dump con compression
mysqldump \
  --single-transaction \
  --quick \
  --routines \
  --triggers \
  --events \
  --all-databases \
  | gzip > "${BACKUP_DIR}/${BACKUP_FILE}"

# Encrypt backup
gpg --encrypt --recipient backup@mcag.it "${BACKUP_DIR}/${BACKUP_FILE}"

# Upload a S3 (off-site)
aws s3 cp "${BACKUP_DIR}/${BACKUP_FILE}.gpg" \
  s3://mcag-backups-offsite/mysql/full/

# Retention: 90 giorni
find "${BACKUP_DIR}" -name "*.gz.gpg" -mtime +90 -delete
```

**Incremental Backup** (ogni 15 minuti):
```bash
# Binlog shipping a replica secondaria
mysqlbinlog --read-from-remote-server \
  --host=mysql-master \
  --raw \
  --stop-never \
  mysql-bin.000001 &

# Replica copia binlog ogni 15min a cold storage
```

**Point-in-Time Recovery (PITR)** abilitato:
- Binlog retention: 7 giorni
- Recovery precision: fino al secondo

### 3.2 File Storage Backups

**Snapshot Incrementale** (hourly):
```bash
#!/bin/bash
# backup-files-hourly.sh

SOURCE_DIR="/var/www/mcag/storage/documents"
BACKUP_DIR="/backups/files/hourly"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Rsync incremental con hardlinks
rsync -a --link-dest="${BACKUP_DIR}/latest" \
  "${SOURCE_DIR}/" \
  "${BACKUP_DIR}/${TIMESTAMP}/"

# Update latest symlink
ln -nsf "${BACKUP_DIR}/${TIMESTAMP}" "${BACKUP_DIR}/latest"

# Sync a S3 Glacier Deep Archive (ultra low cost)
aws s3 sync "${BACKUP_DIR}/${TIMESTAMP}/" \
  s3://mcag-backups-glacier/files/${TIMESTAMP}/ \
  --storage-class DEEP_ARCHIVE
```

### 3.3 Configuration Backups

**Infrastructure as Code** (Git):
- Ansible playbooks
- Docker Compose files
- Nginx configs
- Environment templates

**Encrypted Secrets** (Vault):
- Database credentials
- API keys
- SSL certificates

### 3.4 Backup Testing

**Monthly DR Drill**:
```bash
#!/bin/bash
# dr-test-monthly.sh

echo "🧪 Starting DR Drill..."

# 1. Restore latest backup a ambiente test
php bin/console backup:restore --env=dr-test --latest

# 2. Verifica integrità database
php bin/console db:verify-integrity --env=dr-test

# 3. Run smoke tests
./vendor/bin/pest --testsuite=Smoke --env=dr-test

# 4. Report risultati
./scripts/dr-test-report.sh
```

**Metrics**:
- Restore time
- Data integrity (hash comparison)
- Application functionality (smoke tests pass rate)

---

## 4. FAILOVER PROCEDURES

### 4.1 Database Failover (MySQL Master-Slave)

**Automatic Failover** (ProxySQL):

**Normal Operation**:
```
Client → ProxySQL → MySQL Master (write/read)
                  ↘ MySQL Slave 1 (read)
                  ↘ MySQL Slave 2 (read)
```

**Master Failure Detected**:
```
Client → ProxySQL → MySQL Slave 1 (promoted to Master)
                  ↘ MySQL Slave 2 (repoint to new Master)
```

**Promotion Procedure** (automatic via ProxySQL + Orchestrator):
```sql
-- On Slave 1 (new Master)
STOP SLAVE;
RESET SLAVE ALL;
SET GLOBAL read_only = 0;

-- Update application config
export DB_HOST="mysql-slave-1.mcag.internal"

-- Restart application
systemctl restart mcag-app
```

**Rollback** (quando old Master torna online):
```bash
# Repoint old Master as new Slave
./scripts/mysql-repoint-as-slave.sh mysql-master-old mysql-slave-1
```

### 4.2 Application Server Failover (Load Balancer)

**Multi-AZ Deployment**:
```
        Internet
            ↓
       Load Balancer (HAProxy)
       /       |       \
     App1    App2    App3
   (AZ-A)  (AZ-B)  (AZ-C)
```

**Healthcheck**:
```bash
# HAProxy config
backend mcag_app
  option httpchk GET /health
  http-check expect status 200
  server app1 10.0.1.10:9000 check inter 5s fall 3 rise 2
  server app2 10.0.2.10:9000 check inter 5s fall 3 rise 2
  server app3 10.0.3.10:9000 check inter 5s fall 3 rise 2
```

**Auto-Scaling** (Kubernetes/Docker Swarm):
- Min replicas: 3
- Max replicas: 10
- Scale trigger: CPU > 70% for 5 min

### 4.3 DNS Failover (Route53/Cloudflare)

**Primary Site**: app.mcag.it → 203.0.113.10 (Datacenter Milano)  
**DR Site**: dr.mcag.it → 198.51.100.20 (Datacenter Roma)

**Healthcheck**: HTTP GET app.mcag.it/health ogni 30s  
**Failover**: Se 3 check consecutivi falliscono → switch to DR site

**TTL**: 60 secondi (fast propagation)

---

## 5. RECOVERY PROCEDURES

### 5.1 Full System Recovery da Zero

**Scenario**: Datacenter primario completamente distrutto, ricostruzione totale necessaria

**Steps** (Total Time Estimate: 6-8 ore):

**1. Provision Infrastructure** (1-2 ore):
```bash
# Terraform/Ansible deploy nuovo datacenter
cd infrastructure/
terraform apply -var="environment=disaster-recovery"

# Outputs:
# - 3x Application Servers (Ubuntu 22.04 LTS)
# - 1x MySQL Master + 2x Slaves
# - 1x Redis Cluster (3 nodes)
# - 1x Load Balancer
```

**2. Restore Database** (2-3 ore):
```bash
# Download latest backup da S3
aws s3 cp s3://mcag-backups-offsite/mysql/full/mcag_full_20260127_020000.sql.gz.gpg .

# Decrypt
gpg --decrypt mcag_full_20260127_020000.sql.gz.gpg > mcag_full.sql.gz

# Restore
gunzip < mcag_full.sql.gz | mysql -u root -p

# Apply binlog per PITR (fino a momento disastro)
mysqlbinlog mysql-bin.000042 mysql-bin.000043 | mysql -u root -p

# Verify integrity
php bin/console db:verify-integrity
```

**3. Restore Application Code** (30 min):
```bash
# Clone repo
git clone git@github.com:mcag/mcag-app.git /var/www/mcag
cd /var/www/mcag

# Checkout last stable tag
git checkout v8.3.0

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

**4. Restore File Storage** (1-2 ore, parallelo a db):
```bash
# Download documents da S3
aws s3 sync s3://mcag-backups-glacier/files/latest/ \
  /var/www/mcag/storage/documents/

# Restore permissions
chown -R www-data:www-data /var/www/mcag/storage
chmod -R 755 /var/www/mcag/storage
```

**5. Configuration** (30 min):
```bash
# Copy environment file
cp .env.production.example .env

# Fetch secrets da Vault
vault kv get -format=json secret/mcag/production > secrets.json

# Populate .env
php bin/console env:populate-from-vault secrets.json

# Generate keys
php bin/console key:generate
php bin/console jwt:secret
```

**6. Start Services** (15 min):
```bash
# Database
systemctl start mysql

# Redis
systemctl start redis

# Application
systemctl start mcag-app
systemctl start mcag-queue-worker

# Load balancer
systemctl start haproxy
```

**7. Validation** (30-60 min):
```bash
# Smoke tests
./vendor/bin/pest --testsuite=Smoke

# Data integrity verification
php bin/console verify:data-integrity --full

# Performance baseline
ab -n 1000 -c 100 https://dr.mcag.it/

# Manual UAT (User Acceptance Testing)
# - Login test
# - CRUD operations test
# - Critical workflows test
```

**8. DNS Cutover** (5 min):
```bash
# Update DNS
aws route53 change-resource-record-sets \
  --hosted-zone-id Z1234567890ABC \
  --change-batch file://dns-cutover.json

# Verify propagation
dig app.mcag.it +short
# Should return new DR IP: 198.51.100.20
```

**9. Monitor** (continuous, 48-72h intensive):
- Error rate (Sentry)
- Response time (Prometheus)
- Database replication lag
- User complaints (support tickets)

---

## 6. COMMUNICATION PLAN

### 6.1 Internal Notification

**Incident Declaration**:
```
TO: all@mcag.it
SUBJECT: 🚨 DISASTER RECOVERY ACTIVATED - [SCENARIO]

Team,

A disaster event has occurred: [BRIEF DESCRIPTION]

STATUS: DR procedures initiated at [TIME]
ETA RECOVERY: [ESTIMATE]

WAR ROOM: https://mcag.slack.com/archives/dr-warroom
UPDATES: Every 30 minutes

DO NOT:
- Contact customers directly
- Make public statements
- Modify production systems without IC approval

Incident Commander: [NAME]
```

### 6.2 Customer Communication

**Initial Notification** (entro 1 ora da disastro):
```
Subject: Service Disruption - MCAG System

Gentili Clienti,

Stiamo attualmente riscontrando un'interruzione di servizio dovuta a [REASON].

I nostri team stanno lavorando per ripristinare il servizio al più presto.

TEMPO STIMATO DI RECUPERO: [ETA]

Per aggiornamenti in tempo reale: https://status.mcag.it

Ci scusiamo per il disagio.

MCAG Operations Team
```

**Hourly Updates** (fino a recovery completo)

**Post-Recovery**:
```
Subject: Service Restored - MCAG System

Il servizio è stato completamente ripristinato alle ore [TIME].

CAUSA RADICE: [ROOT CAUSE]
AZIONI PREVENTIVE: [PREVENTION MEASURES]

Grazie per la pazienza.
```

### 6.3 Regulatory/Legal Notification

**Se data loss > 0.1%**:
- Garante Privacy (GDPR): Entro 72h
- Insurance company: Entro 24h
- Board of Directors: Immediato

---

## 7. ROLES & RESPONSIBILITIES

### 7.1 DR Team

| Role | Primary | Responsibilities |
|------|---------|------------------|
| **DR Commander** | CTO | Overall coordination, final decisions |
| **Database Lead** | DBA | Database restore, integrity verification |
| **Infrastructure Lead** | DevOps | Server provisioning, network setup |
| **Application Lead** | Senior Dev | Code deployment, testing |
| **Communications Lead** | Marketing | Customer/stakeholder communication |
| **Legal/Compliance** | Legal Counsel | Regulatory notifications |

### 7.2 Decision Authority

**DR Commander** ha authority per:
- Dichiarare disaster (trigger DR)
- Allocare budget emergenza (fino a €50K)
- Requisition risorse esterne (consultant, hardware)
- Approvare comunicazioni pubbliche

**Board Escalation**: Se recovery cost > €50K o duration > 24h

---

## 8. CONTINUOUS IMPROVEMENT

### 8.1 DR Testing Schedule

**Quarterly Tabletop Exercise**:
- Scenario walkthrough
- Duration: 2 ore
- Participants: DR Team + stakeholders
- Outcome: Updated procedures, identified gaps

**Bi-Annual Live DR Drill**:
- Full recovery simulation (non-production)
- Duration: 1 giornata
- Metrics tracked: RTO achieved, RPO loss, test pass rate
- Post-drill debrief + action items

### 8.2 Plan Maintenance

**Review Triggers**:
- Post ogni disaster/incident major
- Quarterly scheduled review
- Dopo infrastructure changes significativi
- Dopo acquisizioni/merger

**Version Control**:
- DR Plan stored in Git
- Changes require IC + CTO approval
- All team members notified di updates

---

## 9. CONTACT LIST

### 9.1 Internal Contacts

| Role | Name | Phone | Email |
|------|------|-------|-------|
| DR Commander | Ajmeer | +39-XXX-1111 | ajmeer@mcag.it |
| Database Lead | TBD | +39-XXX-2222 | dba@mcag.it |
| Infrastructure | TBD | +39-XXX-3333 | devops@mcag.it |
| Application Lead | TBD | +39-XXX-4444 | dev@mcag.it |
| Communications | TBD | +39-XXX-5555 | comms@mcag.it |

### 9.2 External Contacts

| Category | Provider | Phone | Account # |
|----------|----------|-------|-----------|
| **Hosting** | OVH/AWS | +39-02-XXXXXXX | MCAG-12345 |
| **Database** | Percona Support | +1-888-XXX-XXXX | ENT-98765 |
| **Security** | Incident Response Team | +39-XXX-XXXXXXX | - |
| **Legal** | Studio Legale XYZ | +39-XXX-XXXXXXX | - |
| **Insurance** | Cyber Insurance Co | +39-XXX-XXXXXXX | POL-567890 |

---

## 10. APPENDIX

### 10.1 Recovery Checklists

**[Available as separate downloadable PDF]**

### 10.2 Configuration Backups Inventory

**[Maintained in secure vault]**

### 10.3 Vendor SLA References

**[Contracts stored in legal repository]**

---

## CONCLUSIONE

Un Disaster Recovery Plan robusto è **insurance contro il worst-case scenario**. MCAG implementa best practices enterprise per garantire:

- 🎯 **RTO < 4 ore** (recovery rapido)
- 💾 **RPO < 15 minuti** (data loss minimo)
- 🔄 **Backup testati monthly** (confidence alta)
- 📞 **Communication plan chiaro** (transparency)

**Il DR Plan deve essere un documento vivo**, testato regolarmente e aggiornato con ogni cambio architetturale significativo.

---

**© 2026 Soobadur Mohammad Ajmeer - All Rights Reserved**  
**MCAG Disaster Recovery Plan**  
**Versione**: 1.0 Confidential  
**Data**: 27 Gennaio 2026  
**Review Frequency**: Trimestrale + Post-Incident  
**Classification**: Company Confidential
