#!/bin/sh
set -e

# Ensure .env file exists for self-hosted setup check in public/index.php
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Ensure storage and database directories exist
mkdir -p storage/framework/views storage/framework/cache storage/framework/sessions bootstrap/cache database storage/logs
touch database/database.sqlite

# Set permissions for www-data
chown -R www-data:www-data storage bootstrap/cache database .env
chmod -R 777 storage bootstrap/cache database database/database.sqlite .env

# Generate application key if missing
php artisan key:generate --force || true

# Run database migrations
php artisan migrate --force || true

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Execute Supervisord
exec /usr/bin/supervisord -c /etc/supervisord.conf
