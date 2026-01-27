# 🔄 MIGRATION GUIDE MCAG  
## Migrazione da Sistemi Legacy

**Versione**: 1.0
**Data**: 27 Gennaio 2026

---

## 1. PRE-MIGRATION ASSESSMENT

### Data Audit
```bash
# Check record counts
SELECT COUNT(*) FROM users;  # Zucchetti/old system
SELECT COUNT(*) FROM members;
SELECT COUNT(*) FROM documents;
```

### Compatibility Matrix

| Source System | Effort Level | Duration | Success Rate |
|---------------|--------------|----------|--------------|
| **Excel/Access** | Low | 1-2 settimane | 98% |
| **Zucchetti** | Medium | 3-4 settimane | 95% |
| **TeamSystem** | Medium | 3-4 settimane | 95% |
| **Odoo** | Medium-High | 4-6 settimane | 90% |
| **Custom DB** | High | 6-8 settimane | 85% |

---

## 2. EXPORT DATA FROM SOURCE

### Excel/CSV Export
```bash
# Clean CSV format
"ID","Nome","Cognome","Email","DataNascita"
1,"Mario","Rossi","mario.rossi@example.com","1990-05-15"
```

### Zucchetti SQL Export
```sql
-- Export soci
SELECT 
    id,
    ragione_sociale AS nome,
    NULL AS cognome,
    email,
    codice_fiscale,
    data_nascita,
    created_at
FROM anagrafica_clienti
INTO OUTFILE '/tmp/soci_export.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n';
```

---

## 3. DATA TRANSFORMATION

### Mapping Script Example
```php
// scripts/migrate-from-zucchetti.php
<?php

$sourceCsv = '/tmp/soci_export.csv';
$targetCsv = '/tmp/mcag_import.csv';

$handle = fopen($sourceCsv, 'r');
$output = fopen($targetCsv, 'w');

// Write MCAG header
fputcsv($output, ['matricola', 'nome', 'cognome', 'email', 'codice_fiscale', 'data_nascita']);

while (($row = fgetcsv($handle)) !== false) {
    // Split ragione_sociale into nome/cognome
    [$cognome, $nome] = explode(' ', $row[1], 2);
    
    // Generate matricola
    $matricola = 'SOC-' . str_pad($row[0], 5, '0', STR_PAD_LEFT);
    
    $mcagRow = [
        $matricola,
        $nome ?? '',
        $cognome,
        $row[2],  // email
        $row[3],  // codice_fiscale
        $row[4],  // data_nascita
    ];
    
    fputcsv($output, $mcagRow);
}

fclose($handle);
fclose($output);
```

---

## 4. IMPORT TO MCAG

### Console Command
```bash
php bin/console import:soci --file=/tmp/mcag_import.csv --validate
# Dry-run first, checks format

php bin/console import:soci --file=/tmp/mcag_import.csv --execute
# Actual import
```

### Batch Import (API)
```bash
curl -X POST https://mcag.local/api/import/soci \
  -H "Authorization: Bearer TOKEN" \
  -F "file=@/tmp/mcag_import.csv"
```

---

## 5. VALIDATION POST-IMPORT

### Record Count Comparison
```sql
-- Source system
SELECT COUNT(*) FROM anagrafica_clienti;  # 450

-- MCAG
SELECT COUNT(*) FROM soci;  # Should match: 450
```

### Data Integrity Checks
```bash
php bin/console verify:import --report

# Output:
# ✅ 450 records imported
# ❌ 5 records missing  email (flagged)
# ⚠️  12 records duplicate prevention applied
# ✅ 0 constraint violations
```

---

## 6. PARALLEL RUN (2 WEEKS)

**Strategy**: Run old system + MCAG simultaneously

**Day 1-7**: Dual entry (input data in both)  
**Day 8-14**: MCAG primary, old system read-only  
**Day 15**: Decommission old system

### Daily Sync Script
```bash
# Export delta from old system
./scripts/export-yesterday-changes.sh

# Import to MCAG
php bin/console import:delta --file=delta.csv
```

---

## 7. USER TRAINING

**Session 1** (2h): Navigation, CRUD operations  
**Session 2** (2h): Advanced features (Workshift, Documents)  
**Session 3** (1h): Q&A, troubleshooting

### Training Materials
- Video tutorials (10x5 min clips)
- Quick reference cards (PDF 1-pager)
- Sandbox environment

---

## 8. ROLLBACK PLAN

**If critical issues found within 7 days**:

```bash
# 1. Stop MCAG
php bin/console down

# 2. Reactivate old system
# (keep running read-only during migration)

# 3. Export data entered in MCAG (past 7 days)
php bin/console export:delta --since="-7 days"

# 4. Import delta back to old system

# 5. Root cause analysis
# Fix issues, schedule new migration attempt
```

**Risk Mitigation**: Keep old system accessible (read-only) for 30 days post-migration

---

## 9. COMMON PITFALLS

❌ **Not cleaning data before import** → Garbage data in MCAG  
❌ **Skipping validation step** → Discover issues post-go-live  
❌ **Insufficient training** → User resistance, low adoption  
❌ **No parallel run** → Big-bang risk alto  
❌ **Decommission old system too fast** → No rollback path

---

## CONCLUSION

Migrazione successful richiede:
✅ **Data audit approfondito** (pre-migration)  
✅ **Transformation scripts** (field mapping accurate)  
✅ **Validation rigorosa** (post-import checks)  
✅ **Parallel run** (2 settimane comfort zone)  
✅ **Training intensivo** (user adoption critica)

**Timeline Tipica**: 6-8 settimane (prep + exec + stabilization)

**© 2026 Soobadur Mohammad Ajmeer**
