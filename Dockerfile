# Step 1: Build Frontend Assets (Vite + Vue)
FROM node:20-alpine AS node-builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# Step 2: Production PHP 8.2 + Nginx Image
FROM php:8.2-fpm-alpine

# Install pre-compiled PHP extension installer helper
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Install Nginx, Supervisor, SQLite and required system libraries
RUN apk add --no-cache \
    nginx \
    supervisor \
    sqlite \
    zip \
    unzip

# Fast install pre-compiled PHP extensions
RUN install-php-extensions gd intl pdo_mysql pdo_sqlite bcmath zip opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install Composer dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application files and built Vite assets
COPY . .
COPY --from=node-builder /app/public/build ./public/build

# Optimize Autoloader
RUN composer dump-autoload --optimize

# Ensure required run & configuration directories exist
RUN mkdir -p /run/nginx /etc/nginx/http.d /etc/nginx/conf.d database

# Copy custom Nginx & Supervisor Configs
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf

# Make entrypoint script executable
RUN chmod +x docker/entrypoint.sh

# Verify Nginx configuration syntax at build time
RUN nginx -t

# Create storage, cache folders, logs, and sqlite DB if missing & set full permissions
RUN mkdir -p storage/framework/views storage/framework/cache storage/framework/sessions storage/logs bootstrap/cache \
    && touch database/database.sqlite \
    && touch storage/logs/laravel.log \
    && chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R 777 storage bootstrap/cache database database/database.sqlite storage/logs/laravel.log

EXPOSE 80

# Start Container via Entrypoint script
CMD ["/var/www/html/docker/entrypoint.sh"]
