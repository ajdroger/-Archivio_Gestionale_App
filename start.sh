#!/bin/bash

# Railway Startup Script for Fratellanza Militare Archivio

echo "🚀 Starting Fratellanza Militare Archivio..."

# Create necessary directories if they don't exist
mkdir -p storage/uploads
mkdir -p logs  
mkdir -p backups
mkdir -p db

# Set permissions
chmod -R 775 storage
chmod -R 775 logs
chmod -R 775 backups

# Check if database exists, if not run migrations
if [ ! -f "database.sqlite" ]; then
    echo "📊 Database not found. Creating database..."
    touch database.sqlite
    chmod 664 database.sqlite
    
    # Run migrations if phinx is available
    if [ -f "phinx.php" ]; then
        echo "🔄 Running database migrations..."
        vendor/bin/phinx migrate -e production || echo "⚠️ Migrations not available or failed"
    fi
fi

# Start PHP built-in server
echo "✅ Starting PHP server on port $PORT..."
php -S 0.0.0.0:$PORT -t public
