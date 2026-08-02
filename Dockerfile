# ============================================================
# ETAPA 1: COMPILAR CSS Y JAVASCRIPT CON VITE
# ============================================================
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./

RUN npm ci

COPY resources ./resources
COPY public ./public
COPY vite.config.js ./

RUN npm run build


# ============================================================
# ETAPA 2: APLICACIÓN LARAVEL CON PHP Y APACHE
# ============================================================
FROM php:8.4-apache

ENV APP_ENV=production
ENV APP_DEBUG=false
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

WORKDIR /var/www/html

# Dependencias del sistema y extensiones requeridas por Laravel.
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

# Composer.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copiar código del proyecto.
COPY . .

# Instalar dependencias PHP de producción.
RUN composer install \
        --no-dev \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader \
        --no-scripts \
    && php artisan package:discover --ansi

# Copiar los recursos compilados por Vite.
COPY --from=frontend /app/public/build ./public/build

# Permisos necesarios para Laravel.
RUN chown -R www-data:www-data \
        storage \
        bootstrap/cache \
    && chmod -R 775 \
        storage \
        bootstrap/cache

EXPOSE 80

CMD ["apache2-foreground"]