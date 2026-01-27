# 💾 GUIDA BACKUP & RESTORE MCAG
## Procedure Complete Backup e Ripristino

**Versione**: 1.0  
**Data**: 27 Gennaio 2026  
**Sistema**: MCAG v8.3.0+

---

## 1. AUTOMATED BACKUP SETUP

### 1.1 Database Backup (Cron)

```bash
#!/bin/bash
# /opt/mcag/scripts/backup-db.sh

BACKUP_DIR="/backups/mysql"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="mcag_production"

# Full backup
mysqldump --single-transaction \
  --quick \
  --lock-tables=false \
  $DB_NAME | gzip > "$BACKUP_DIR/full_$DATE.sql.gz"

# Encrypt
gpg --encrypt --recipient backup@mcag.it "$BACKUP_DIR/full_$DATE.sql.gz"

# Upload to S3
aws s3 cp "$BACKUP_DIR/full_$DATE.sql.gz.gpg" s3://mcag-backups/

# Cleanup old backups (>30 days)
find $BACKUP_DIR -name "*.gz.gpg" -mtime +30 -delete

echo "Backup completed: full_$DATE.sql.gz.gpg"
```

**Crontab Entry**:
```cron
# Daily at 2 AM
0 2 * * * /opt/mcag/scripts/backup-db.sh >> /var/log/mcag-backup.log 2>&1
```

### 1.2 File Storage Backup

```bash
#!/bin/bash
# /opt/mcag/scripts/backup-files.sh

SOURCE="/var/www/mcag/storage"
DEST="/backups/files/$(date +%Y%m%d)"

# Incremental with hardlinks
rsync -a --link-dest=/backups/files/latest \
  $SOURCE/ $DEST/

# Update latest symlink
ln -nsf $DEST /backups/files/latest

# Sync to S3 Glacier
aws s3 sync $DEST/ s3://mcag-backups-glacier/files/ \
  --storage-class DEEP_ARCHIVE
```

**Crontab**: Hourly
```cron
0 * * * * /opt/mcag/scripts/backup-files.sh
```

---

## 2. MANUAL BACKUP PROCEDURES

### 2.1 Full System Backup

```bash
# 1. Database
php bin/console backup:create --type=full

# 2. Files
tar -czf mcag-files-$(date +%Y%m%d).tar.gz /var/www/mcag/storage

# 3. Configuration
tar -czf mcag-config-$(date +%Y%m%d).tar.gz \
  /var/www/mcag/.env \
  /var/www/mcag/config \
  /etc/nginx/sites-available/mcag

# 4. Verify integrity
md5sum mcag-*.tar.gz > checksums.md5
```

### 2.2 Pre-Deployment Backup

```bash
# Before major update
php bin/console backup:create --tag="pre-deploy-v8.4.0"
```

---

## 3. RESTORE PROCEDURES

### 3.1 Database Restore (Full)

```bash
# 1. Download from S3
aws s3 cp s3://mcag-backups/full_20260127_020000.sql.gz.gpg .

# 2. Decrypt
gpg --decrypt full_20260127_020000.sql.gz.gpg > full_20260127_020000.sql.gz

# 3. Decompress
gunzip full_20260127_020000.sql.gz

# 4. Stop application
php bin/console down

# 5. Restore
mysql mcag_production < full_20260127_020000.sql

# 6. Verify
php bin/console db:verify-integrity

# 7. Restart
php bin/console up
```

### 3.2 Partial Restore (Single Table)

```bash
# Extract specific table
gunzip < backup.sql.gz | sed -n '/CREATE TABLE `soci`/,/CREATE TABLE/p' > soci_only.sql

# Restore
mysql mcag_production < soci_only.sql
```

### 3.3 Point-in-Time Recovery (PITR)

```bash
# 1. Restore last full backup
mysql mcag_production < full_20260127_020000.sql

# 2. Apply binlogs until specific time
mysqlbinlog \
  --stop-datetime="2026-01-27 14:30:00" \
  mysql-bin.000042 mysql-bin.000043 | \
  mysql mcag_production

# 3. Verify data at that timestamp
mysql mcag_production -e "SELECT MAX(created_at) FROM soci;"
```

---

## 4. OFF-SITE BACKUP (S3)

### 4.1 S3 Configuration

```bash
# Install AWS CLI
sudo apt install awscli

# Configure
aws configure
# AWS Access Key: [your-key]
# AWS Secret Key: [your-secret]
# Region: eu-west-1
# Format: json
```

### 4.2 Lifecycle Policies

```json
{
  "Rules": [{
    "Id": "mcag-backup-lifecycle",
    "Status": "Enabled",
    "Transitions": [
      {
        "Days": 30,
        "StorageClass": "GLACIER"
      },
      {
        "Days": 365,
        "StorageClass": "DEEP_ARCHIVE"
      }
    ],
    "Expiration": {
      "Days": 2555
    }
  }]
}
```

---

## 5. BACKUP TESTING

### 5.1 Monthly DR Drill

```bash
#!/bin/bash
# test-restore.sh

echo "Starting DR drill..."

# 1. Download latest backup
LATEST=$(aws s3 ls s3://mcag-backups/ | sort | tail -1 | awk '{print $4}')
aws s3 cp "s3://mcag-backups/$LATEST" .

# 2. Restore to test DB
mysql mcag_test < "$LATEST"

# 3. Run integrity checks
php bin/console db:verify --env=test

# 4. Run smoke tests
./vendor/bin/pest --testsuite=Smoke --env=test

# 5. Report
./scripts/dr-test-report.sh
```

---

## 6. BACKUP INTEGRITY

### 6.1 Checksum Verification

```bash
# Generate checksums during backup
md5sum backup.sql.gz > backup.md5

# Verify before restore
md5sum -c backup.md5
```

### 6.2 Test Restore

```bash
# Periodically test restore to temp DB
mysql temp_restore < backup.sql
mysql temp_restore -e "SELECT COUNT(*) FROM soci;"
mysql -e "DROP DATABASE temp_restore;"
```

---

## 7. DISASTER RECOVERY SCENARIOS

### Scenario 1: Accidental DELETE

```sql
-- Backup shows 100 soci deleted at 14:25

-- 1. Restore to temp DB at 14:24
-- 2. Export deleted records
SELECT * FROM soci INTO OUTFILE '/tmp/deleted_soci.csv';

-- 3. Import back to production
LOAD DATA INFILE '/tmp/deleted_soci.csv' INTO TABLE soci;
```

### Scenario 2: Ransomware

```bash
# 1. Identify last clean backup (before infection)
aws s3 ls s3://mcag-backups/ | grep "202601

26"

# 2. Rebuild server from scratch
# 3. Restore clean backup
# 4. Apply binlogs carefully (stop before ransomware)
```

---

## 8. RETENTION POLICY

| Backup Type | Retention | Storage | Cost/Month |
|-------------|-----------|---------|------------|
| **Daily Full** | 30 days | S3 Standard | €25 |
| **Weekly Archive** | 90 days | S3 Glacier | €8 |
| **Monthly Archive** | 7 years | S3 Deep Archive | €2 |
| **Incremental Files** | 7 days | Local Disk | €0 |

---

## CONCLUSION

Backup strategy MCAG garantisce:
- ✅ RPO < 15 minuti (binlog continuous)
- ✅ RTO < 4 ore (restore + validation)
- ✅ Multi-tier storage (cost-optimized)
- ✅ Tested monthly (confidence alta)

**© 2026 Soobadur Mohammad Ajmeer**
