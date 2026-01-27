# 📈 SCALING GUIDE MCAG
## Guida Scalabilità Architettura

**Versione**: 1.0
**Data**: 27 Gennaio 2026

---

## 1. VERTICAL SCALING (Scale-Up)

### Current Baseline (Single Server)
- **CPU**: 4 vCPU
- **RAM**: 16 GB
- **Disk**: 500 GB SSD
- **Capacity**: ~200 concurrent users

### Upgrade Path

**Level 2** (500 users):
- CPU: 8 vCPU
- RAM: 32 GB
- Cost: +€150/mese

**Level 3** (1000 users):
- CPU: 16 vCPU
- RAM: 64 GB
- Cost: +€400/mese

**Level 4** (2000+ users):
- Move to horizontal scaling

---

## 2. HORIZONTAL SCALING (Scale-Out)

### Load Balancer Setup

```nginx
# HAProxy config
frontend mcag_frontend
    bind *:443 ssl crt /path/to/cert.pem
    default_backend mcag_app

backend mcag_app
    balance roundrobin
    option httpchk GET /health
    server app1 10.0.1.10:9000 check
    server app2 10.0.2.10:9000 check
    server app3 10.0.3.10:9000 check
```

### Database Replication

**Master-Slave Setup**:
```sql
-- On Master
CREATE USER 'repl'@'%' IDENTIFIED BY 'password';
GRANT REPLICATION SLAVE ON *.* TO 'repl'@'%';

-- On Slave
CHANGE MASTER TO
    MASTER_HOST='10.0.1.5',
    MASTER_USER='repl',
    MASTER_PASSWORD='password',
    MASTER_LOG_FILE='mysql-bin.000001',
    MASTER_LOG_POS=107;
START SLAVE;
```

**Read/Write Splitting**:
```php
// config/database.php
'connections' => [
    'mysql_write' => [
        'host' => '10.0.1.5',  // Master
    ],
    'mysql_read' => [
        'host' => ['10.0.1.6', '10.0.1.7'],  // Slaves
    ],
]
```

---

## 3. CACHING STRATEGY

### Redis Cluster (3 nodes)

```bash
# Enable cluster mode
redis-cli --cluster create \
    10.0.1.20:6379 \
    10.0.1.21:6379 \
    10.0.1.22:6379 \
    --cluster-replicas 1
```

### Cache Usage Patterns

**Page Cache** (5 min TTL):
```php
cache()->remember("dashboard_stats", 300, function () {
    return DB::table('soci')->selectRaw('COUNT(*) as total')->first();
});
```

**Invalidation**:
```php
// On socio create/update/delete
cache()->forget('dashboard_stats');
```

---

## 4. DATABASE OPTIMIZATION

### Partitioning Large Tables

```sql
-- Partition soci by year
ALTER TABLE soci
PARTITION BY RANGE (YEAR(created_at)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p2026 VALUES LESS THAN (2027)
);
```

### Indexes Critical Paths

```sql
CREATE INDEX idx_soci_email ON soci(email);
CREATE INDEX idx_soci_active_created ON soci(active, created_at);
CREATE INDEX idx_documenti_socio_type ON documenti(socio_id, tipo);
```

---

## 5. CDN FOR STATIC ASSETS

### CloudFlare Setup
- Cache images, CSS, JS
- GZIP compression
- Minification automatic
- DDoS protection

**Config** (.htaccess):
```apache
<FilesMatch "\.(jpg|jpeg|png|gif|css|js)$">
    Header set Cache-Control "max-age=2592000, public"
</FilesMatch>
```

---

## 6. ASYNC PROCESSING (Queues)

### Heavy Tasks to Queue

```php
// Email sending
dispatch(new SendWelcomeEmail($socio));

// PDF generation
dispatch(new GenerateSocioPdfJob($socio));

// Data exports
dispatch(new ExportSociCsvJob($filters));
```

### Queue Workers (Multiple)

```bash
# Supervisor config
[program:mcag-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/mcag/bin/console queue:work
autostart=true
autorestart=true
numprocs=5  # 5 parallel workers
```

---

## 7. MONITORING AT SCALE

### Metrics to Track

- **Application**: Request rate, error rate, response time (p95, p99)
- **Database**: Query rate, slow queries (>1s), connections used
- **Redis**: Hit rate, evictions, memory usage
- **Infrastructure**: CPU, memory, disk I/O, network

### Alerts

```yaml
# Prometheus alert rules
- alert: HighErrorRate
  expr: rate(http_requests_total{status=~"5.."}[5m]) > 0.05
  for: 5m
  annotations:
    summary: "Error rate >5%"

- alert: DatabaseConnectionPoolExhausted
  expr: mysql_connections_active / mysql_connections_max > 0.9
  for: 2m
```

---

## 8. COST OPTIMIZATION

| Users | Architecture | Monthly Cost |
|-------|--------------|--------------|
| **0-200** | Single server | €80 |
| **200-500** | Vertical scale | €230 |
| **500-2K** | Horizontal (3 app + LB) | €620 |
| **2K-5K** | + DB replicas + Redis cluster | €1.200 |
| **5K+** | Multi-region + CDN | €2.500+ |

---

## CONCLUSION

Scala MCAG da 200 a 5.000+ utenti con:
✅ **Vertical scale** (fino 1K users)
✅ **Horizontal scale** (load balancer + replicas)
✅ **Database optimization** (read replicas, partitioning)
✅ **Caching aggressive** (Redis cluster)
✅ **Async processing** (queue workers multipli)

**© 2026 Soobadur Mohammad Ajmeer**
