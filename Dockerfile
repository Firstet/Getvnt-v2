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
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    icu-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev

# Install PHP extensions required by Laravel & Getvnt (gd, intl, pdo_mysql, zip, opcache, etc.)
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

# Configure Nginx for Laravel public directory
RUN mkdir -p /etc/nginx/http.d
RUN echo 'server { \
    listen 80; \
    server_name _; \
    root /var/www/html/public; \
    index index.php index.html; \
    charset utf-8; \
    location / { \
        try_files $uri $uri/ /index.php?$query_string; \
    } \
    location = /favicon.ico { access_log off; log_not_found off; } \
    location = /robots.txt  { access_log off; log_not_found off; } \
    error_page 404 /index.php; \
    location ~ \.php$ { \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name; \
        include fastcgi_params; \
    } \
    location ~ /\.(?!well-known).* { \
        deny all; \
    } \
}' > /etc/nginx/http.d/default.conf

# Set permissions for Laravel storage & bootstrap/cache
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

# Start PHP-FPM and Nginx
CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
