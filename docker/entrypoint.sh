#!/bin/sh
set -e

# Ensure storage and database directories exist
mkdir -p storage/framework/views storage/framework/cache storage/framework/sessions bootstrap/cache database storage/logs
touch database/database.sqlite

# Set permissions for www-data
chown -R www-data:www-data storage bootstrap/cache database
chmod -R 777 storage bootstrap/cache database database/database.sqlite

# Run database migrations
php artisan migrate --force || true

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Execute Supervisord
exec /usr/bin/supervisord -c /etc/supervisord.conf
