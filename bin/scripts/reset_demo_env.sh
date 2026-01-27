#!/bin/bash
# MCAG Demo Environment Reset Script
# Usage: ./reset_demo_env.sh
# Cron: 0 4 * * * /path/to/reset_demo_env.sh >> /var/log/mcag_reset.log 2>&1

# Configuration
APP_ROOT="/var/www/html/mcag"
DB_NAME="mcag_demo"
DB_USER="root"
DB_PASS="" # Set via env var for security
GOLDEN_DUMP="$APP_ROOT/database/dumps/demo_golden_state.sql"
LOG_FILE="$APP_ROOT/storage/logs/demo_reset.log"

timestamp=$(date +%Y-%m-%d_%H-%M-%S)

echo "[$timestamp] STARTED: Demo Reset Protocol Initiated..." | tee -a "$LOG_FILE"

# 1. Check if Golden Dump exists
if [ ! -f "$GOLDEN_DUMP" ]; then
    echo "[$timestamp] ERROR: Golden State dump not found at $GOLDEN_DUMP" | tee -a "$LOG_FILE"
    exit 1
fi

# 2. Database Reset
echo "[$timestamp] INFO: Resetting Database '$DB_NAME'..." | tee -a "$LOG_FILE"
# Assuming MySQL client is installed
mysql -u"$DB_USER" -p"$DB_PASS" -e "DROP DATABASE IF EXISTS $DB_NAME; CREATE DATABASE $DB_NAME;" 2>> "$LOG_FILE"
mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$GOLDEN_DUMP" 2>> "$LOG_FILE"

if [ $? -eq 0 ]; then
    echo "[$timestamp] SUCCESS: Database restored." | tee -a "$LOG_FILE"
else
    echo "[$timestamp] ERROR: Database restore failed." | tee -a "$LOG_FILE"
    exit 1
fi

# 3. Aggressive Cleanup (Logs, Cache, Uploads)
echo "[$timestamp] INFO: Cleaning Filesystem..." | tee -a "$LOG_FILE"
rm -rf "$APP_ROOT/storage/logs/"*.log
rm -rf "$APP_ROOT/storage/cache/"*
# Keep .gitignore or specialized uploads if needed, but for demo usually clear all
rm -rf "$APP_ROOT/public/uploads/temp/"*

# 4. Re-Seed Dynamic Data (Optional - e.g., Set today's date for demo data)
# php "$APP_ROOT/bin/console" db:seed --class=DemoDateUpdater

# 5. Permission Fix (Just in case)
# chown -R www-data:www-data "$APP_ROOT/storage"

echo "[$timestamp] COMPLETED: Demo Environment is Fresh & Ready." | tee -a "$LOG_FILE"
echo "--------------------------------------------------------" | tee -a "$LOG_FILE"

exit 0
