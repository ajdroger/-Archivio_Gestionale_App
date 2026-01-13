# 📚 GUIDA REDIS - MCAG

## Panoramica

Il sistema MCAG v2.0 utilizza Redis per:
- **Caching** distribuito e performante
- **Rate Limiting** persistente
- **Queue System** (opzionale)

Redis migliora drammaticamente le performance e la scalabilità del sistema.

---

## Installazione Redis

### Windows (con Chocolatey)

```powershell
choco install redis-64
```

Oppure scaricare da: https://redis.io/download/

### Linux (Ubuntu/Debian)

```bash
sudo apt update
sudo apt install redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

### Docker

```bash
docker run -d --name redis -p 6379:6379 redis:latest
```

---

## Configurazione

### 1. Variabili Ambiente (.env)

```env
REDIS_ENABLED=true
REDIS_SCHEME=tcp
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_DB=0
```

### 2. Verifica Connessione

```bash
redis-cli ping
# Output: PONG
```

---

## Utilizzo

### Caching

Il sistema cachea automaticamente:

- **Lista soci**: TTL 5 minuti
- **Statistiche dashboard**: TTL 15 minuti
- **Aggregazioni**: TTL 10 minuti

#### Cache Invalidation

Quando si modifica un socio, la cache viene invalidata automaticamente:

```php
$cacheService->invalidateSoci();
```

### Rate Limiting

Con Redis, il rate limiting è:
- **Persistente** (sopravvive ai restart)
- **Distribuito** (funziona con più server)
- **Preciso** (no race conditions)

Rate limits configurati:
- Login: 5 req/min per IP
- API Global: 100 req/min
- Export CSV: 30 req/min

### Queue System

Background jobs processati in modo asincrono:

#### Avvia Worker

```bash
php bin/workers/queue_worker.php default
```

#### Job Disponibili

1. **GeneratePdfJob** - Generazione PDF asincrona
2. **SendEmailJob** - Invio email asincrono
3. **BackupDatabaseJob** - Backup database automatico

---

## Monitoraggio

### Redis CLI

```bash
# Connetti
redis-cli

# Info generale
INFO

# Memoria usata
INFO memory

# Keys totali
DBSIZE

# Monitor real-time
MONITOR

# Flush database (ATTENZIONE!)
FLUSHDB
```

### Cache Statistics

Endpoint: `/devtools` → Cache Stats

Mostra:
- Hit rate
- Numero di hit/miss
- Memoria Redis utilizzata

---

## Performance

### Con Redis (v2.0)

| Operazione | Tempo | Note |
|------------|-------|------|
| Lista soci (cached) | <10ms | ⚡ 95% più veloce |
| Statistiche dashboard | <20ms | ⚡ 90% più veloce |
| Rate limit check | <1ms | ⚡ Instant |

### Senza Redis (Fallback)

Il sistema funziona anche senza Redis:
- Cache disabilitata
- Rate limiting su filesystem
- Performance ridotte ma funzionale

---

## Troubleshooting

### Redis non raggiungibile

Il sistema si degrada in modo graceful:

```php
if (!$redis->isEnabled()) {
    // Fallback to file-based caching
}
```

### Clear cache manualmente

```bash
redis-cli FLUSHDB
```

Oppure:  
DevTools → System → Clear All Cache

### Memoria piena

```bash
# Check memoria
redis-cli INFO memory

# Configura max memory (2GB)
redis-cli CONFIG SET maxmemory 2gb
redis-cli CONFIG SET maxmemory-policy allkeys-lru
```

---

## Best Practices

1. **Non disabilitare Redis in produzione** - Performance critiche
2. **Monitora memoria** - Imposta `maxmemory` appropriato
3. **Persistence** - Abilita RDB/AOF per backup
4. **Firewall** - Proteggi porta 6379
5. **Password** - Configura `requirepass` in produzione

---

## Avanzato

### Redis Persistence

```bash
# In redis.conf
save 900 1      # Save after 900s if >= 1 key changed
save 300 10     # Save after 300s if >= 10 keys changed
save 60 10000   # Save after 60s if >= 10000 keys changed
```

### Cluster Setup

Per deployment multi-server, configurare Redis Cluster o Sentinel per alta disponibilità.

---

**Versione Guida**: 2.0  
**Ultima modifica**: 26 Dicembre 2025

