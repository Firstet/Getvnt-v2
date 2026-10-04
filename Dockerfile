# Step 1: Build Frontend Assets (Vite + Vue)
FROM node:20-alpine AS node-builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# Step 2: Production PHP 8.2 + Nginx Image
FROM php:8.2-fpm-alpine

# Install Nginx, Supervisor, and required system libraries
RUN apk add --no-cache \
    nginx \
    supervisor \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    icu-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev

# Install PHP extensions required by Laravel & Getvnt
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd intl pdo pdo_mysql bcmath zip opcache

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

# Remove default nginx configs to prevent duplicate default server conflicts
RUN rm -rf /etc/nginx/http.d/* /etc/nginx/conf.d/*

# Copy custom Nginx & Supervisor Configs
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf

# Verify Nginx configuration syntax at build time
RUN nginx -t

# Create storage and cache folders if missing & set full permissions
RUN mkdir -p storage/framework/views storage/framework/cache storage/framework/sessions bootstrap/cache \
    && chmod -R 777 storage bootstrap/cache

EXPOSE 80

# Start Supervisor (manages both PHP-FPM and Nginx)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
