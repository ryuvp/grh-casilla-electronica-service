# syntax=docker/dockerfile:1
FROM php:8.2-fpm

# Dependencias del sistema + herramientas de red
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    unzip zip curl git \
    ca-certificates \
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
    bcmath \
    pcntl \
    exif \
    opcache \
 && pecl install redis && docker-php-ext-enable redis

# Copiar Composer desde la imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Configuración PHP-FPM para alto rendimiento
RUN cat > /usr/local/etc/php-fpm.d/zz-custom.conf <<'EOF'
[www]
pm = static
pm.max_children = 50
pm.max_requests = 500
request_terminate_timeout = 30s
EOF

# Límites de PHP
RUN echo 'max_execution_time = 60' > /usr/local/etc/php/conf.d/zz-custom.ini \
    && echo 'memory_limit = 256M' >> /usr/local/etc/php/conf.d/zz-custom.ini

# OPcache + JIT optimizado para producción
RUN cat > /usr/local/etc/php/conf.d/zz-opcache-optimized.ini <<'EOF'
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 20000
opcache.revalidate_freq = 0
opcache.validate_timestamps = 0
opcache.fast_shutdown = 1
opcache.enable_file_override = 1
opcache.jit_buffer_size = 256M
opcache.jit = tracing
EOF

# Copiar manifest de Composer primero para aprovechar caché Docker
COPY backend/composer.json backend/composer.lock ./

# Instalar dependencias sin scripts
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts

# Copiar código del proyecto
COPY backend/ ./

# Limpiar cachés de bootstrap y regenerar
RUN rm -f bootstrap/cache/*.php \
    && php artisan package:discover --ansi

# Dar permisos a storage y bootstrap/cache
RUN rm -rf /var/www/html/storage/logs/* \
    && mkdir -p /var/www/html/storage/logs \
                /var/www/html/storage/framework/cache/data \
                /var/www/html/storage/framework/sessions \
                /var/www/html/storage/framework/views \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
