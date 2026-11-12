##########
# Base PHP (común para FPM y CLI)
##########

FROM php:8.3-fpm AS base

# Dependencias del sistema y extensiones PHP necesarias para Laravel + Horizon
RUN apt-get update && apt-get install -y \
    default-mysql-client \
    libzip-dev \
    zip \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    git \
    unzip \
    && docker-php-ext-install -j"$(nproc)" iconv pdo pdo_mysql zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd \
    && docker-php-ext-install mbstring exif pcntl bcmath opcache intl \
    && pecl install redis \
    && docker-php-ext-enable redis

# Xdebug (opcional, activado vía XDEBUG_MODE)
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Copiar Composer desde la imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Configuración de PHP
COPY docker/php/php.ini /usr/local/etc/php/

# Directorio de trabajo unificado
WORKDIR /var/www/html

# Crear directorios de Laravel con permisos adecuados
RUN mkdir -p bootstrap/cache storage/framework storage/logs \
    && chown -R www-data:www-data bootstrap storage \
    && chmod -R 775 bootstrap/cache storage

##########
# Target FPM (servicio php-fpm)
##########

FROM base AS fpm

CMD ["php-fpm"]

##########
# Target CLI (horizon, scheduler, tareas artisan)
##########

FROM base AS cli

# Sin supervisor, sin php-fpm: los comandos (horizon, schedule:work, etc.)
# se definen desde docker-compose como proceso principal.
CMD ["php", "-v"]

