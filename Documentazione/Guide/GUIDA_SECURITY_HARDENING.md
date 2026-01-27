# 🔒 SECURITY HARDENING GUIDE MCAG
## Guida Rafforzamento Sicurezza

**Versione**: 1.0
**Data**: 27 Gennaio 2026

---

## 1. SERVER HARDENING

### Disable Unnecessary Services
```bash
systemctl disable bluetooth  
systemctl disable cups  # Print server
systemctl stop bluetooth cups
```

### SSH Hardening
```bash
# /etc/ssh/sshd_config
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
Port 2222  # Non-standard port
```

### Firewall (UFW)
```bash
ufw default deny incoming
ufw default allow outgoing
ufw allow 2222/tcp  # SSH
ufw allow 443/tcp   # HTTPS
ufw allow 3306/tcp from 10.0.0.0/24  # MySQL (internal only)
ufw enable
```

---

## 2. DATABASE SECURITY

### Remove anonymous users
```sql
DELETE FROM mysql.user WHERE User='';
FLUSH PRIVILEGES;
```

### Strong passwords
```sql
ALTER USER 'mcag'@'localhost' 
IDENTIFIED BY 'Str0ng!P@ssw0rd_32chars_Min';
```

### Disable remote root

```sql
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1');
```

###  Enable SSL connections
```sql
GRANT ALL ON mcag_production.* TO 'mcag'@'%' REQUIRE SSL;
```

---

## 3. APPLICATION SECURITY

### Environment Variables (Never commit .env!)
```bash
# .gitignore
.env
.env.production
```

### Secrets Management (Vault)
```bash
# Store in Vault, not .env directly
vault kv put secret/mcag/db password="xxx"

# Application loads from Vault
DB_PASSWORD=$(vault kv get -field=password secret/mcag/db)
```

### Disable Debug Mode (Production)
```bash
# .env.production
APP_ENV=production
APP_DEBUG=false
```

---

## 4. WEB SERVER (Nginx)

### Security Headers
```nginx
# /etc/nginx/sites-available/mcag
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "no-referrer-when-downgrade" always;
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';" always;
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
```

### Hide Server Version
```nginx
server_tokens off;
```

### Rate Limiting
```nginx
limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;

location /login {
    limit_req zone=login burst=3;
}
```

---

## 5. SSL/TLS

### Free SSL (Let's Encrypt)
```bash
certbot --nginx -d app.mcag.it
```

### Force HTTPS Redirect
```nginx
server {
    listen 80;
    server_name app.mcag.it;
    return 301 https://$server_name$request_uri;
}
```

### Strong Cipher Suites
```nginx
ssl_protocols TLSv1.2 TLSv1.3;
ssl_ciphers 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256...';
ssl_prefer_server_ciphers on;
```

---

## 6. FILE PERMISSIONS

### Secure Permissions
```bash
# Application files
chown -R www-data:www-data /var/www/mcag
chmod -R 755 /var/www/mcag

# Storage (writable)
chmod -R 775 /var/www/mc ag/storage
chmod -R 775 /var/www/mcag/cache

# .env (read-only root)
chmod 600 /var/www/mcag/.env
chown root:root /var/www/mcag/.env
```

---

## 7. INTRUSION DETECTION

### Fail2Ban (Block Brute Force)
```bash
apt install fail2ban

# /etc/fail2ban/jail.local
[mcag-login]
enabled = true
port = http,https
filter = mcag-login
logpath = /var/www/mcag/storage/logs/mcag.log
maxretry = 5
bantime = 600
```

### AIDE (File Integrity)
```bash
apt install aide
aide --init
aide --check  # Daily cron
```

---

## 8. LOGGING & MONITORING

### Centralized Logging
```bash
# Ship logs to SIEM
./vendor/bin/logstash -f logstash-mcag.conf
```

### Security Alerts
```yaml
# Prometheus alert
- alert: UnauthorizedAccessAttempt
  expr: rate(http_requests_total{status="401"}[5m]) > 10
  annotations:
    summary: "High 401 rate detected"
```

---

## 9. BACKUPS ENCRYPTED

### GPG Encryption
```bash
# Encrypt backup
gpg --encrypt --recipient security@mcag.it backup.sql

# Decrypt
gpg --decrypt backup.sql.gpg > backup.sql
```

---

## 10. SECURITY AUDIT CHECKLIST

**Monthly Review**:
- [ ] Update OS packages (`apt update && apt upgrade`)
- [ ] Review firewall rules
- [ ] Check SSL certificate expiry
- [ ] Rotate API keys/passwords (quarterly)
- [ ] Review user permissions (revoke inactive)
- [ ] Scan for vulnerabilities (`composer audit`, `npm audit`)
- [ ] Check security logs (failed logins, SQLi attempts)

---

## CONCLUSION

Security hardening MCAG best practices:
✅ **Server**: Firewall, SSH hardening, minimal services  
✅ **Database**: Strong passwords, SSL, no remote root  
✅ **App**: Secrets in Vault, debug OFF, CSRF enabled  
✅ **Web**: Security headers, rate limiting, HTTPS forced  
✅ **Monitoring**: Fail2Ban, AIDE, SIEM integration

**Security Score Target**: A+ (SSL Labs), 95+ (OWASP ZAP)

**© 2026 Soobadur Mohammad Ajmeer**
