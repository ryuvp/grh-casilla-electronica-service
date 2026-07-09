# syntax=docker/dockerfile:1
FROM php:8.2-fpm

# Dependencias del sistema + herramientas de red
RUN apt-get update && apt-get install -y --no-install-recommends \
    # Dependencias PHP/Laravel
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    unzip zip curl git nano \
    p7zip-full \
    ca-certificates \
    # Herramientas de red (debug)
    iproute2 \
    iputils-ping \
    net-tools \
    dnsutils \
    wget \
    telnet \
    netcat-openbsd \
    tcpdump \
 && rm -rf /var/lib/apt/lists/*

# Extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install \
    gd \
    mbstring \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    zip \
    xml \
    exif \
    pcntl \
    bcmath

# Copiar Composer desde la imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar manifest de Composer primero para aprovechar caché Docker
COPY backend/composer.json backend/composer.lock ./

# Instalar dependencias de PHP (sin dependencias de desarrollo)
# Se posponen los scripts de Composer hasta que el proyecto completo exista en la imagen.
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts

# Copiar código del proyecto
COPY backend/ ./

# Limpiar cachés de bootstrap arrastrados desde el workspace antes de regenerarlos en producción
RUN rm -f bootstrap/cache/*.php \
    && php artisan package:discover --ansi

# Dar permisos adecuados
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Exponer el puerto por donde PHP-FPM escucha
EXPOSE 9000

# Comando de arranque
CMD ["php-fpm"]
