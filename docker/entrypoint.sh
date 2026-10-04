#!/bin/sh
set -e

# Force default DB_CONNECTION to sqlite and sanitize session config
export DB_CONNECTION=sqlite
export DB_DATABASE=/var/www/html/database/database.sqlite
export DB_HOST=127.0.0.1
export QUEUE_CONNECTION=sync
export APP_KEY="${APP_KEY:-base64:c1hSM1lhUjhZNm5OdnBRTHBTM2s5S1RKN2d4TzRFR1k=}"
export SESSION_DOMAIN=""
export SESSION_SECURE_COOKIE=false

# Ensure .env file exists for self-hosted setup check in public/index.php
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Sanitize .env file
if grep -q 'DB_CONNECTION="mysql"' .env || grep -q 'DB_CONNECTION=mysql' .env; then
    sed -i 's/DB_CONNECTION="mysql"/DB_CONNECTION="sqlite"/g' .env
    sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/g' .env
    sed -i 's/DB_HOST="mysql"/DB_HOST="127.0.0.1"/g' .env
    sed -i 's/DB_HOST=mysql/DB_HOST=127.0.0.1/g' .env
    sed -i 's|DB_DATABASE="getvnt"|DB_DATABASE="/var/www/html/database/database.sqlite"|g' .env
    sed -i 's|DB_DATABASE=getvnt|DB_DATABASE=/var/www/html/database/database.sqlite|g' .env
    sed -i 's/QUEUE_CONNECTION="database"/QUEUE_CONNECTION="sync"/g' .env
    sed -i 's/QUEUE_CONNECTION=database/QUEUE_CONNECTION=sync/g' .env
fi

# Clear placeholder SESSION_DOMAIN and enforce SESSION_SECURE_COOKIE=false
sed -i 's/SESSION_DOMAIN=.*/SESSION_DOMAIN=/g' .env || true
sed -i 's/SESSION_SECURE_COOKIE=.*/SESSION_SECURE_COOKIE=false/g' .env || true
sed -i 's|APP_URL=.*|APP_URL=https://getvnt-x9t6pu-92d7c6-169-58-52-97.sslip.io|g' .env || true

# Ensure APP_KEY is valid base64 key
if ! grep -q 'APP_KEY=base64:' .env; then
    sed -i 's/APP_KEY=.*/APP_KEY=base64:c1hSM1lhUjhZNm5OdnBRTHBTM2s5S1RKN2d4TzRFR1k=/g' .env
fi

# Ensure storage and database directories exist
mkdir -p storage/framework/views storage/framework/cache storage/framework/sessions bootstrap/cache database storage/logs
touch database/database.sqlite
touch storage/logs/laravel.log

# Initial permission set for setup commands
chown -R www-data:www-data storage bootstrap/cache database .env
chmod -R 777 storage bootstrap/cache database database/database.sqlite storage/logs/laravel.log .env

# Generate application key if missing
php artisan key:generate --force || true

# Run database migrations
php artisan migrate --force || true

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Re-apply full write permissions to storage, logs, and database after artisan commands
touch storage/logs/laravel.log
chown -R www-data:www-data storage bootstrap/cache database .env
chmod -R 777 storage bootstrap/cache database database/database.sqlite storage/logs/laravel.log .env

# Execute Supervisord
exec /usr/bin/supervisord -c /etc/supervisord.conf
