#!/bin/sh
set -e

# Default DB configuration to MySQL if unset
export DB_CONNECTION="${DB_CONNECTION:-mysql}"
export DB_HOST="${DB_HOST:-mysql}"
export DB_PORT="${DB_PORT:-3306}"
export DB_DATABASE="${DB_DATABASE:-getvnt}"
export DB_USERNAME="${DB_USERNAME:-getvnt}"
export DB_PASSWORD="${DB_PASSWORD:-Chimapaul2019@@}"

# Ensure .env file exists for setup check
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
    fi
fi

# Ensure storage and database directories exist
mkdir -p storage/framework/views storage/framework/cache storage/framework/sessions bootstrap/cache database storage/logs
touch database/database.sqlite 2>/dev/null || true
touch storage/logs/laravel.log 2>/dev/null || true

# Permission set
chown -R www-data:www-data storage bootstrap/cache database 2>/dev/null || true
chmod -R 777 storage bootstrap/cache database storage/logs 2>/dev/null || true

# Generate application key if missing in .env and not set in environment
if [ -f .env ] && ! grep -q 'APP_KEY=base64:' .env && [ -z "$APP_KEY" ]; then
    php artisan key:generate --force || true
fi

# Run database migrations
php artisan migrate --force || true

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Re-apply write permissions
chown -R www-data:www-data storage bootstrap/cache database 2>/dev/null || true
chmod -R 777 storage bootstrap/cache database storage/logs 2>/dev/null || true

# Execute Supervisord
exec /usr/bin/supervisord -c /etc/supervisord.conf

