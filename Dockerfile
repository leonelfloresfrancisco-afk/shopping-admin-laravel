# ============================================================
# ETAPA 1: DEPENDENCIAS PHP
# ============================================================
FROM composer:2 AS php_dependencies

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts


# ============================================================
# ETAPA 2: COMPILAR CSS Y JAVASCRIPT
# ============================================================
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./

RUN npm ci

COPY . .

# Vite necesita vendor para localizar archivos de Laravel,
# Livewire, Flux y otros paquetes durante la compilación.
COPY --from=php_dependencies /app/vendor ./vendor

RUN npm run build


# ============================================================
# ETAPA 3: APLICACIÓN LARAVEL CON PHP Y APACHE
# ============================================================
FROM php:8.4-apache

ENV APP_ENV=production
ENV APP_DEBUG=false
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
ENV PORT=10000

WORKDIR /var/www/html

# Configuración PHP específica para Render.
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        curl \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        intl \
        zip \
        bcmath \
        gd \
        opcache \
    && a2enmod rewrite headers expires \
    && sed -ri \
        -e 's!/var/www/html!/var/www/html/public!g' \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf \
    && rm -rf /var/lib/apt/lists/*

COPY . .

COPY --from=php_dependencies /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

RUN php artisan package:discover --ansi \
    && php artisan storage:link || true

# Render monta los archivos secretos con acceso para el grupo 1000.
# Se crea el grupo si todavía no existe y se añade www-data.
RUN if ! getent group 1000 > /dev/null; then \
        groupadd --gid 1000 render-secrets; \
    fi \
    && RENDER_SECRET_GROUP="$(getent group 1000 | cut -d: -f1)" \
    && usermod --append --groups "$RENDER_SECRET_GROUP" www-data \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 10000

CMD ["sh", "-c", "sed -ri \"s/^Listen 80$/Listen ${PORT}/\" /etc/apache2/ports.conf && sed -ri \"s/<VirtualHost \\*:80>/<VirtualHost *:${PORT}>/\" /etc/apache2/sites-available/000-default.conf && apache2-foreground"]