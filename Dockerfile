# ==============================================================================
# Multi-stage Dockerfile for Koyeb / Render / Cloud Deployment
# Stack: PHP 8.4-FPM, Nginx, Node.js 22 (Vite + Vue 3), MySQL PDO
# ==============================================================================

# ------------------------------------------------------------------------------
# Stage 1: PHP Composer Dependencies
# ------------------------------------------------------------------------------
FROM composer:2 AS composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize --no-dev

# ------------------------------------------------------------------------------
# Stage 2: Node.js Frontend Build
# ------------------------------------------------------------------------------
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package*.json ./
RUN npm ci || npm install

COPY resources/ resources/
COPY vite.config.js tsconfig.json ./
COPY --from=composer /app/vendor /app/vendor

RUN npm run build

# ------------------------------------------------------------------------------
# Stage 3: Final Production Runtime (Alpine + PHP 8.4-FPM + Nginx)
# ------------------------------------------------------------------------------
FROM php:8.4-fpm-alpine AS runtime

# Install system dependencies, Nginx, and Supervisor
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    ca-certificates \
    bash

# Install official PHP extension installer script
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions pdo_mysql mbstring zip gd bcmath intl opcache pcntl

# Configure PHP-FPM to listen only on loopback 127.0.0.1:9000 so external scanners (Render) don't detect port 9000
RUN sed -i 's/listen = 9000/listen = 127.0.0.1:9000/' /usr/local/etc/php-fpm.d/zz-docker.conf

WORKDIR /var/www/html

# Copy codebase
COPY . .
COPY --from=composer /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

# Copy configuration files
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh && \
    mkdir -p storage bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

# Expose default HTTP ports (80 for standard/Koyeb, 10000 for Render)
EXPOSE 80 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

