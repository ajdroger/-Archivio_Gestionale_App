# 🚀 DEPLOYMENT GUIDE - Fratellanza Militare

## Pre-Deployment Checklist

### ✅ Environment Configuration

1. **Update `.env` for Production**:
```env
APP_ENV=production
DB_CONNECTION=mysql
DB_HOST=your_mysql_host
DB_PORT=3306
DB_DATABASE=fratellanza_db
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password
SMTP_HOST=your_smtp_host
SMTP_USER=your_email
SMTP_PASS=your_smtp_password
```

2. **File Permissions**:
```bash
chmod 600 .env
chmod -R 755 public
chmod -R 775 storage logs backups
chown -R www-data:www-data storage logs backups
```

3. **Dependencies**:
```bash
composer install --no-dev --optimize-autoloader
npm run build
```

### ✅ Database Setup

1. **Create MySQL Database**:
```sql
CREATE DATABASE fratellanza_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'fratellanza_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON fratellanza_db.* TO 'fratellanza_user'@'localhost';
FLUSH PRIVILEGES;
```

2. **Run Migration**:
```bash
php bin/maintenance/migrate_to_mysql.php
```

3. **Verify Schema**:
```bash
php bin/maintenance/check_db_connection.php
```

### ✅ Web Server Configuration

#### Apache (Recommended)

**VirtualHost Configuration** (`/etc/apache2/sites-available/fratellanza.conf`):
```apache
<VirtualHost *:80>
    ServerName fratellanza.example.com
    Redirect permanent / https://fratellanza.example.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName fratellanza.example.com
    DocumentRoot /var/www/fratellanza/public

    SSLEngine on
    SSLCertificateFile /path/to/cert.pem
    SSLCertificateKeyFile /path/to/privkey.pem

    <Directory /var/www/fratellanza/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/fratellanza_error.log
    CustomLog ${APACHE_LOG_DIR}/fratellanza_access.log combined
</VirtualHost>
```

Enable site:
```bash
a2enmod ssl rewrite
a2ensite fratellanza
systemctl reload apache2
```

#### Nginx (Alternative)

```nginx
server {
    listen 80;
    server_name fratellanza.example.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name fratellanza.example.com;
    root /var/www/fratellanza/public;
    index index.php;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \\.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\\.(env|git|htaccess) {
        deny all;
    }
}
```

### ✅ Security Hardening

1. **Disable PHP Functions**:
Add to `php.ini`:
```ini
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,parse_ini_file,show_source
```

2. **Setup Firewall**:
```bash
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable
```

3. **Setup Fail2Ban**:
```bash
apt-get install fail2ban
# Configure /etc/fail2ban/jail.local for Apache/Nginx
systemctl enable fail2ban
```

### ✅ Automated Backups

**Cron Job** (`crontab -e`):
```bash
# Daily MySQL backup at 2 AM
0 2 * * * /usr/bin/mysqldump -u fratellanza_user -p'password' fratellanza_db | gzip > /var/backups/fratellanza/db_$(date +\%Y\%m\%d).sql.gz

# Weekly cleanup (keep last 30 days)
0 3 * * 0 find /var/backups/fratellanza -name "db_*.sql.gz" -mtime +30 -delete

# Daily file backup
30 2 * * * tar -czf /var/backups/fratellanza/files_$(date +\%Y\%m\%d).tar.gz /var/www/fratellanza/storage/uploads
```

### ✅ Monitoring

**Setup Log Rotation** (`/etc/logrotate.d/fratellanza`):
```
/var/www/fratellanza/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
}
```

**Health Check** (every 5 min via cron):
```bash
*/5 * * * * curl -f https://fratellanza.example.com/ || echo "Site down!" | mail -s "Alert" admin@example.com
```

---

## Post-Deployment Validation

1. **Run Full Test Suite**:
```bash
vendor/bin/pest
```

2. **Check Logs**:
```bash
tail -f logs/app.log
tail -f /var/log/apache2/fratellanza_error.log
```

3. **Verify SSL**:
```bash
openssl s_client -connect fratellanza.example.com:443
```

4. **Performance Test**:
```bash
ab -n 100 -c 10 https://fratellanza.example.com/
```

---

## Rollback Plan

**If deployment fails**:

1. **Restore Database**:
```bash
gunzip < /var/backups/fratellanza/db_YYYYMMDD.sql.gz | mysql -u fratellanza_user -p fratellanza_db
```

2. **Restore Files**:
```bash
tar -xzf /var/backups/fratellanza/files_YYYYMMDD.tar.gz -C /var/www/fratellanza/
```

3. **Revert Code**:
```bash
git checkout previous_stable_tag
composer install --no-dev
```

---

## Support & Maintenance

- **Logs Location**: `/var/www/fratellanza/logs/`
- **Backup Location**: `/var/backups/fratellanza/`
- **Documentation**: `/var/www/fratellanza/Documentazione/`
- **Emergency Contact**: IT Administrator

**Deployed By**: Soobadur Mohammad Ajmeer  
**Last Updated**: 2025-12-25
