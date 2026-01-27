# 🔧 GUIDA TROUBLESHOOTING MCAG
## Risoluzione Problemi Comuni - Manuale Operativo

**Versione**: 1.0  
**Data**: 27 Gennaio 2026  
**Sistema**: MCAG v8.3.0+  
**Tipo**: Guida Tecnica Support

---

## 📋 INDICE

1. [Quick Reference](#1-quick-reference)
2. [Authentication Issues](#2-authentication-issues)
3. [Database Problems](#3-database-problems)
4. [Email/SMTP Failures](#4-emailsmtp-failures)
5. [Redis Cache Issues](#5-redis-cache-issues)
6. [Performance Degradation](#6-performance-degradation)
7. [2FA Not Working](#7-2fa-not-working)
8. [PDF Generation Errors](#8-pdf-generation-errors)
9. [Workshift Module](#9-workshift-module)
10. [Log Interpretation Guide](#10-log-interpretation-guide)

---

## 1. QUICK REFERENCE

### Most Common Issues (80% tickets)

| Issue | Frequency | Severity | Avg Resolution |
|-------|-----------|----------|----------------|
| [Forgot Password](#21-password-reset-not-received) | 35% | Low | 5 min |
| [2FA Token Expired](#71-totp-token-always-invalid) | 18% | Medium | 10 min |
| [Slow Page Load](#61-page-load-20s) | 12% | Medium | 30 min |
| [Email Not Sending](#41-emails-not-sending) | 10% | High | 20 min |
| [PDF Generation Fails](#81-pdf-generation-timeout) | 8% | Medium | 15 min |
| [Database Connection Lost](#31-cannot-connect-to-database) | 5% | Critical | 2 min |
| [Redis Down](#51-redis-connection-refused) | 4% | High | 5 min |
| [Others](#) | 8% | Varia | Varia |

### Emergency Contacts

- **On-Call Engineer**: +39-XXX-XXXXXXX
- **Email Support**: support@mcag.it
- **Slack Emergency**: #mcag-emergency

---

## 2. AUTHENTICATION ISSUES

### 2.1 Password Reset Not Received

**Symptom**: User clicks "Reset Password", no email arrives

**Root Causes**:
1. SMTP misconfigured
2. Email in spam folder
3. User typed wrong email
4. Queue worker not running

**Diagnosis**:

```bash
# Check logs
tail -f storage/logs/mcag.log | grep "password_reset"

# Check queue status
php bin/console queue:status

# Test SMTP connection
php bin/console email:test --to=test@example.com
```

**Solutions**:

**A. SMTP Not Working**:
```bash
# Verify .env
cat .env | grep MAIL_

# Expected:
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=app-password  # NOT regular password!
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@mcag.it
```

**B. Queue Not Running**:
```bash
# Check queue worker
ps aux | grep queue:work

# If not running, start it
php bin/console queue:work &

# Or use systemd
systemctl start mcag-queue-worker
systemctl enable mcag-queue-worker
```

**C. Check Spam Folder**: Instruct user to check spam/junk

**D. Manual Password Reset** (emergency):
```bash
php bin/console user:reset-password --email=user@example.com
# Generates temporary password, send via support ticket
```

---

### 2.2 "Invalid Credentials" Despite Correct Password

**Symptom**: User inserts correct password, gets "Invalid credentials"

**Root Causes**:
1. Account disabled administratively
2. Password hash corrupted (rare)
3. Caps Lock on
4. Browser autocomplete wrong password

**Diagnosis**:

```bash
# Check user status
php bin/console user:info --email=user@example.com

# Output should show:
# Status: active  ← If "disabled", that's the issue
```

**Solutions**:

**A. Account Disabled**:
```bash
php bin/console user:enable --email=user@example.com
```

**B. Password Hash Corrupted**:
```bash
# Reset password manually
php bin/console user:reset-password --email=user@example.com
```

**C. Browser Autocomplete**:
- Instruct user: use Incognito mode, type password manually

---

### 2.3 Session Expires Too Quickly

**Symptom**: User logged out after 5-10 minutes inactivity

**Root Cause**: Session lifetime too short

**Diagnosis**:

```bash
# Check session config
cat config/session.php | grep lifetime

# Check .env
cat .env | grip SESSION_LIFETIME
```

**Solution**:

```bash
# Edit .env
SESSION_LIFETIME=120  # 120 minutes = 2 hours

# Restart application
php bin/console config:cache
systemctl restart mcag-app
```

**Default Recommended**: 60-120 minuti (balance security vs UX)

---

## 3. DATABASE PROBLEMS

### 3.1 Cannot Connect to Database

**Symptom**: Error "SQLSTATE[HY000] [2002] Connection refused"

**Root Causes**:
1. MySQL service down
2. Wrong credentials in .env
3. Firewall blocking port 3306
4. max_connections reached

**Diagnosis**:

```bash
# Check MySQL service
systemctl status mysql
# or
systemctl status mariadb

# Test connection manually
mysql -h localhost -u mcag -p
# If prompts for password and connects → credentials OK

# Check firewall
sudo iptables -L | grep 3306

# Check max connections
mysql -e "SHOW VARIABLES LIKE 'max_connections';"
mysql -e "SHOW STATUS LIKE 'Threads_connected';"
```

**Solutions**:

**A. MySQL Down**:
```bash
systemctl start mysql
systemctl enable mysql  # Auto-start on boot
```

**B. Wrong Credentials**:
```bash
# Edit .env
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=mcag_production
DB_USERNAME=mcag
DB_PASSWORD=<CORRECT_PASSWORD>

# Clear config cache
php bin/console config:clear
```

**C. Firewall Block**:
```bash
# Allow MySQL port
sudo ufw allow 3306/tcp
# or iptables
sudo iptables -A INPUT -p tcp --dport 3306 -j ACCEPT
```

**D. Max Connections Reached**:
```bash
# Edit MySQL config
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf

# Add/modify:
[mysqld]
max_connections = 500  # Increase from default 151

# Restart MySQL
systemctl restart mysql
```

---

### 3.2 Slow Database Queries

**Symptom**: Page loads 5-20 seconds, DB is bottleneck

**Root Causes**:
1. Missing indexes
2. Slow query (N+1 problem)
3. Table too large, not partitioned
4. MySQL not optimized

**Diagnosis**:

```bash
# Enable slow query log
mysql -e "SET GLOBAL slow_query_log = 'ON';"
mysql -e "SET GLOBAL long_query_time = 1;"  # Log queries >1s

# Analyze slow queries
sudo pt-query-digest /var/log/mysql/slow.log
```

**Solutions**:

**A. Add Missing Indexes**:
```sql
-- Example: Slow query on email lookup
EXPLAIN SELECT * FROM users WHERE email = 'user@example.com';
-- If "type: ALL" → full table scan, need index

CREATE INDEX idx_users_email ON users(email);
```

**B. Optimize Query** (N+1 problem):
```php
// BAD: N+1 queries
$soci = Socio::all();  // 1 query
foreach ($soci as $socio) {
    echo $socio->documenti->count();  // N queries!
}

// GOOD: Eager loading
$soci = Socio::with('documenti')->get();  // 2 queries total
foreach ($soci as $socio) {
    echo $socio->documenti->count();  // No extra query
}
```

**C. Table Partitioning** (if >1M rows):
```sql
-- Partition soci by year
ALTER TABLE soci
PARTITION BY RANGE (YEAR(created_at)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p2026 VALUES LESS THAN (2027),
    PARTITION pmax VALUES LESS THAN MAXVALUE
);
```

**D. MySQL Optimization**:
```bash
# Run mysqltuner
sudo apt install mysqltuner
sudo mysqltuner

# Follow recommendations (buffer pool size, query cache, etc.)
```

---

### 3.3 Database Disk Full

**Symptom**: Error "ERROR 1114 (HY000): The table 'xxx' is full"

**Diagnosis**:

```bash
# Check disk usage
df -h

# Check MySQL data directory size
sudo du -sh /var/lib/mysql
```

**Solutions**:

**A. Immediate** (free space):
```bash
# Truncate old logs
mysql -e "PURGE BINARY LOGS BEFORE DATE(NOW() - INTERVAL 7 DAY);"

# Clean audit logs (if not needed)
mysql mcag_production -e "TRUNCATE TABLE audit_logs_archive;"
```

**B. Long-term**:
```bash
# Archive old data to S3/cold storage
php bin/console backup:archive --older-than=1year

# Enable log rotation
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
# Add:
expire_logs_days = 7
max_binlog_size = 100M
```

---

## 4. EMAIL/SMTP FAILURES

### 4.1 Emails Not Sending

**Symptom**: System says "Email sent" but user doesn't receive

**Root Causes**:
1. SMTP credentials wrong
2. Port blocked by firewall/ISP
3. Emails going to spam
4. Daily send limit reached (Gmail/SendGrid)

**Diagnosis**:

```bash
# Check logs
tail -f storage/logs/mcag.log | grep "Mailer"

# Test SMTP manually
php bin/console email:test --to=your-email@example.com --subject="Test"

# Check queue
php bin/console queue:failed
```

**Solutions**:

**A. SMTP Credentials**:
```bash
# For Gmail, use App Password (NOT regular password)
# Generate at: https://myaccount.google.com/apppasswords

# .env:
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=<16-char-app-password>  # No spaces!
MAIL_ENCRYPTION=tls
```

**B. Port Blocked**:
```bash
# Test SMTP port connectivity
telnet smtp.gmail.com 587
# If "Connection refused" → firewall/ISP blocking

# Try alternative port (465 SSL)
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
```

**C. Emails in Spam**:
- Set up SPF record: `v=spf1 include:_spf.google.com ~all`
- Set up DKIM
- Set up DMARC
- Warm up new IP (gradual send volume increase)

**D. Daily Limit Reached**:
```bash
# Gmail free: 500/day limit
# SendGrid free: 100/day limit

# Check sent count today
php bin/console email:stats --today

# If exceeded, upgrade plan or wait 24h
```

---

### 4.2 Email Templates Not Rendering

**Symptom**: Emails sent but appear broken (no CSS, wrong layout)

**Root Cause**: Inline CSS not applied, email client limitations

**Solution**:

```bash
# Ensure inline CSS preprocessor running
php bin/console email:compile-templates

# Test template rendering
php bin/console email:preview --template=socio_welcome

# Use email-safe CSS (no flexbox, limited support)
# Tools: https://templates.mailchimp.com/resources/inline-css/
```

---

## 5. REDIS CACHE ISSUES

### 5.1 Redis Connection Refused

**Symptom**: Error "Connection refused [tcp://127.0.0.1:6379]"

**Root Cause**: Redis service down or wrong config

**Diagnosis**:

```bash
# Check Redis status
systemctl status redis

# Test connection
redis-cli ping
# Should return: PONG
```

**Solutions**:

**A. Redis Down**:
```bash
systemctl start redis
systemctl enable redis
```

**B. Redis on Different Host**:
```bash
# Edit .env
REDIS_HOST=redis.mcag.internal
REDIS_PORT=6379
REDIS_PASSWORD=<password-if-applicable>

php bin/console config:cache
systemctl restart mcag-app
```

---

### 5.2 Cache Stale Data

**Symptom**: Changes not reflecting, seeing old data

**Root Cause**: Cache not invalidated properly

**Solution**:

```bash
# Clear all cache
php bin/console cache:clear

# Flush Redis
redis-cli FLUSHALL

# Restart application
systemctl restart mcag-app
```

---

## 6. PERFORMANCE DEGRADATION

### 6.1 Page Load >20s

**Symptom**: Dashboard takes 20+ seconds to load

**Diagnosis Workflow**:

```bash
# 1. Check server load
top
# High CPU? → Check processes hogging CPU
# High  memory? → Check memory leaks

# 2. Check slow queries
sudo pt-query-digest /var/log/mysql/slow.log | head -50

# 3. Check Redis hit rate
redis-cli INFO stats | grep keyspace_hits
# Low hit rate? → Cache not effective

# 4. Check PHP-FPM workers
sudo systemctl status php8.2-fpm
# All workers busy? → Increase pm.max_children

# 5. Profile with Apex
# Enable Sentry profiling, analyze transaction traces
```

**Solutions** (apply in order):

**1. Optimize Slow Queries**:
- Add indexes (see section 3.2)
- Refactor N+1 queries

**2. Increase PHP-FPM Workers**:
```bash
sudo nano /etc/php/8.2/fpm/pool.d/www.conf

# Modify:
pm = dynamic
pm.max_children = 50  # Increase from 5
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 15

sudo systemctl restart php8.2-fpm
```

**3. Increase Opcache**:
```bash
sudo nano /etc/php/8.2/fpm/conf.d/10-opcache.ini

opcache.memory_consumption=256  # Increase from 128
opcache.max_accelerated_files=20000

sudo systemctl restart php8.2-fpm
```

**4. Enable Page Caching**:
```php
// In controller
return response()->view('dashboard')
    ->header('Cache-Control', 'public,max-age=300');  // 5 min
```


---

### 6.2 High Memory Usage

**Symptom**: Server running out of memory, OOM killer

**Diagnosis**:

```bash
# Check memory usage
free -h

# Find memory hogs
ps aux --sort=-%mem | head -10
```

**Solutions**:

**A. Memory Leak** in Application:
```bash
# Restart PHP-FPM workers periodically
pm.max_requests = 500  # Restart worker after 500 requests

# Monitor with:
watch -n 1 'ps aux | grep php-fpm'
```

**B. Increase Server RAM**: Upgrade instance

**C. Add Swap** (temporary):
```bash
sudo fallocate -l 4G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile

# Make permanent
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

---

## 7. 2FA NOT WORKING

### 7.1 TOTP Token Always Invalid

**Symptom**: User enters 6-digit code from Google Authenticator, always "Invalid"

**Root Causes**:
1. Server time out of sync (most common!)
2. Secret key corrupted in DB
3. User using wrong account in Authenticator

**Diagnosis**:

```bash
# Check server time
date
# Compare with: https://time.is/

# Check time sync service
timedatectl status
# "System clock synchronized: yes" ← Should be yes

# Check user's TOTP secret in DB
mysql mcag_production -e "SELECT email, totp_secret FROM users WHERE email='user@example.com';"
# Should be 32-char base32 string
```

**Solutions**:

**A. Server Time Out of Sync**:
```bash
# Install NTP
sudo apt install ntp

# Sync time
sudo ntpdate pool.ntp.org

# Enable NTP service
sudo timedatectl set-ntp on

# Verify
timedatectl status
```

**B. Reset 2FA for User** (emergency bypass):
```bash
php bin/console 2fa:reset --email=user@example.com

# Email user new QR code
php bin/console 2fa:send-setup --email=user@example.com
```

**C. User Re-Scan QR Code**:
- Instruct: delete old account in Authenticator, scan fresh QR code

---

### 7.2 Recovery Codes Not Working

**Symptom**: User lost phone, recovery code doesn't work

**Root Cause**: Recovery codes already used or not generated

**Solution**:

```bash
# Check if user has recovery codes
mysql mcag_production -e "SELECT recovery_codes FROM users WHERE email='user@example.com';"

# If NULL or empty, generate new ones
php bin/console 2fa:generate-recovery-codes --email=user@example.com

# Email codes to user (via support, verify identity first!)
```

---

## 8. PDF GENERATION ERRORS

### 8.1 PDF Generation Timeout

**Symptom**: Error "PDF generation timeout after 60s"

**Root Cause**: Complex PDF (many pages/images), Dompdf slow

**Solutions**:

**A. Increase Timeout**:
```bash
# Edit .env
PDF_TIMEOUT=180  # 180 seconds = 3 minutes

php bin/console config:cache
```

**B. Optimize PDF Template**:
```php
// Reduce image sizes
<img src="..." width="200">  // Explicit width

// Paginate large tables
@if (count($items) > 100)
    // Generate multiple PDFs
@endif
```

**C. Use Queue** (async PDF generation):
```php
// Instead of generating sync
dispatch(new GeneratePdfJob($socio));

// User gets email when ready
```

---

### 8.2 PDF Missing Images

**Symptom**: PDF generated but images don't appear

**Root Cause**: Relative paths not working in PDF context

**Solution**:

```php
// BAD: Relative path
<img src="/images/logo.png">

// GOOD: Absolute path
<img src="{{ public_path('images/logo.png') }}">

// OR: Base64 embed
<img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/logo.png'))) }}">
```

---

## 9. WORKSHIFT MODULE

### 9.1 Shifts Not Saving

**Symptom**: User creates shift, clicks Save, but doesn't appear

**Root Causes**:
1. JS error blocking AJAX request
2. Validation failing silently
3. Database constraint violation

**Diagnosis**:

```bash
# Check browser console (F12)
# Look for JS errors (red text)

# Check network tab
# POST /api/workshift/shifts → Status code?
#   200: OK
#   422: Validation error
#   500: Server error

# Check server logs
tail -f storage/logs/mcag.log | grep Workshift
```

**Solutions**:

**A. JS Error**:
```javascript
// Check if shift-management.js loaded
console.log(typeof saveShift);  // Should be "function"

// Clear browser cache
Ctrl+Shift+Delete → Clear cache
```

**B. Validation Error**:
```bash
# Check validation rules
# Shift start time must be < end time
# No overlapping shifts for same employee

# Server returns validation errors in response
# Check network tab → Response
```

**C. Database Constraint**:
```bash
# Check logs
tail -f storage/logs/mcag.log | grep "SQLSTATE"

# Common: Duplicate entry (shift already exists)
# Solution: Add unique constraint check in code
```

---

### 9.2 AI Optimizer Not Suggesting

**Symptom**: Click "Optimize Schedule", no suggestions appear

**Root Cause**: Ollama service down or not configured

**Diagnosis**:

```bash
# Check Ollama status
systemctl status ollama
# or
curl http://localhost:11434/api/version

# Check Ollama model installed
ollama list
# Should show: mistral or llama2
```

**Solutions**:

**A. Ollama Down**:
```bash
systemctl start ollama
systemctl enable ollama
```

**B. Model Not Installed**:
```bash
ollama pull mistral
# Wait for download (~4GB)

# Verify
ollama list
```

**C. MCAG Config**:
```bash
# Edit .env
OLLAMA_API_URL=http://localhost:11434
OLLAMA_MODEL=mistral

php bin/console config:cache
systemctl restart mcag-app
```

---

## 10. LOG INTERPRETATION GUIDE

### 10.1 Common Error Patterns

**Pattern**: `SQLSTATE[23000]: Integrity constraint violation`
- **Meaning**: Trying to insert duplicate unique key or violate foreign key
- **Action**: Check validation, ensure unique constraints respected

**Pattern**: `Class 'App\Service\XyzService' not found`
- **Meaning**: Autoloader issue or missing dependency
- **Action**: `composer dump-autoload`

**Pattern**: `Maximum execution time of 30 seconds exceeded`
- **Meaning**: Script timeout (long query/operation)
- **Action**: Increase `max_execution_time` in php.ini or optimize code

**Pattern**: `Allowed memory size of X bytes exhausted`
- **Meaning**: PHP memory limit reached
- **Action**: Increase `memory_limit` in php.ini or optimize memory usage

**Pattern**: `CSRF token mismatch`
- **Meaning**: Form submitted with expired/wrong CSRF token
- **Action**: Session expired, user needs to refresh page

---

### 10.2 Log Levels

| Level | Severity | When to Check |
|-------|----------|---------------|
| **EMERGENCY** | System unusable | Immediately, page on-call |
| **ALERT** | Action required immediately | Within 5 minutes |
| **CRITICAL** | Critical conditions | Within 15 minutes |
| **ERROR** | Error conditions | Daily review |
| **WARNING** | Warning conditions | Weekly review |
| **INFO** | Informational | Monthly review |
| **DEBUG** | Debug messages | Only when troubleshooting |

---

## 11. SUPPORT TICKET TEMPLATE

When creating support ticket, include:

```markdown
### Issue Description
[Brief description of the problem]

### Steps to Reproduce
1. Go to...
2. Click on...
3. See error

### Expected Behavior
[What should happen]

### Actual Behavior
[What actually happens]

### Environment
- MCAG Version: [e.g. v8.3.0]
- Browser: [e.g. Chrome 120]
- OS: [e.g. Windows 11]
- User Role: [e.g. Admin]

### Logs/Screenshots
[Paste relevant logs or attach screenshots]

### Error Message (if any)
```
[Exact error message]
```

### Attempted Solutions
[What you already tried]
```

---

## CONCLUSION

Questa guida copre **90%+ dei problemi comuni**. Se il problema persiste dopo aver seguito i troubleshooting steps:

1. 📧 Email: support@mcag.it (Response < 12h)
2. 📞 Phone: +39-XXX-XXXXXXX (Business hours)
3. 🚨 Emergency: Slack #mcag-emergency (24/7)

**Escalation**: Se problema CRITICAL (sistema down, data loss), contattare direttamente On-Call Engineer.

---

**© 2026 Soobadur Mohammad Ajmeer - All Rights Reserved**  
**MCAG Troubleshooting Guide**  
**Versione**: 1.0  
**Data**: 27 Gennaio 2026  
**Aggiornamento**: Trimestrale o quando nuovi pattern emergono
