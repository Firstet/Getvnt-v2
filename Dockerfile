# Step 1: Build Frontend Assets (Vite + Vue)
FROM node:20-alpine AS node-builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# Step 2: Production PHP 8.2 + Nginx Image
FROM php:8.2-fpm-alpine

# Install Nginx, Supervisor, SQLite and required system libraries
RUN apk add --no-cache \
    nginx \
    supervisor \
    sqlite \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    icu-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev

# Install PHP extensions required by Laravel & Getvnt (gd, intl, pdo_sqlite, pdo_mysql, bcmath, zip, opcache)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd intl pdo pdo_sqlite pdo_mysql bcmath zip opcache

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

# Verify Nginx configuration syntax at build time
RUN nginx -t

# Create storage, cache folders, and sqlite DB if missing & set full permissions
RUN mkdir -p storage/framework/views storage/framework/cache storage/framework/sessions bootstrap/cache \
    && touch database/database.sqlite \
    && chmod -R 777 storage bootstrap/cache database/database.sqlite

EXPOSE 80

# Start Supervisor (manages both PHP-FPM and Nginx)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
