#!/bin/sh
set -e

# Ensure storage directories exist and are writable
mkdir -p storage/framework/views storage/framework/cache storage/framework/sessions bootstrap/cache database
touch database/database.sqlite
chmod -R 777 storage bootstrap/cache database/database.sqlite

# Run database migrations automatically
php artisan migrate --force || true

# Clear and optimize Laravel caches
php artisan config:clear
php artisan cache:clear
php artisan route:cache
php artisan view:cache

# Execute Supervisord (manages PHP-FPM and Nginx)
exec /usr/bin/supervisord -c /etc/supervisord.conf
