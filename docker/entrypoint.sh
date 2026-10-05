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

# Ensure APP_KEY is populated if environment passed an empty APP_KEY=""
if [ -z "$APP_KEY" ]; then
    if [ -f .env ] && grep -q '^APP_KEY=base64:' .env; then
        export APP_KEY="$(grep '^APP_KEY=base64:' .env | head -n1 | cut -d'=' -f2-)"
    else
        php artisan key:generate --force || true
        if [ -f .env ] && grep -q '^APP_KEY=base64:' .env; then
            export APP_KEY="$(grep '^APP_KEY=base64:' .env | head -n1 | cut -d'=' -f2-)"
        fi
    fi
fi

# If MAIL_HOST is unconfigured or mail.getvnt.com (unresolved DNS), default MAIL_MAILER to log
if [ "$MAIL_HOST" = "mail.getvnt.com" ] || [ -z "$MAIL_HOST" ] || [ "$MAIL_HOST" = "127.0.0.1" ] || [ "$MAIL_HOST" = "localhost" ]; then
    if [ "$MAIL_MAILER" = "smtp" ] || [ -z "$MAIL_MAILER" ]; then
        export MAIL_MAILER="log"
    fi
fi

# Run database migrations
php artisan migrate --force || true

# Ensure PHP-FPM preserves environment variables
echo "clear_env = no" >> /usr/local/etc/php-fpm.d/www.conf 2>/dev/null || true
echo "clear_env = no" >> /etc/php82/php-fpm.d/www.conf 2>/dev/null || true

# Cache config and routes for high performance and consistent env values
php artisan config:cache
php artisan route:cache
php artisan view:clear

# Re-apply write permissions
chown -R www-data:www-data storage bootstrap/cache database 2>/dev/null || true
chmod -R 777 storage bootstrap/cache database storage/logs 2>/dev/null || true

# Execute Supervisord
exec /usr/bin/supervisord -c /etc/supervisord.conf

