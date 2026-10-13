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

# Dimensionado de PHP-FPM: TODO por variables de entorno, nada cableado en la imagen.
#
# El tamano del pool depende de la CPU y la memoria del pod, que es una decision
# de DESPLIEGUE, no de la imagen. Con ENV, una misma imagen sirve para todos los
# ambientes y se ajusta desde el manifiesto de k8s sin reconstruir ni republicar.
# PHP-FPM interpola ${VAR} en el pool (verificado), incluidos los php_admin_value.
#
# Los ENV de abajo son solo el DEFAULT. Al subir FPM_MAX_CHILDREN hay tres techos:
#   1) Memoria del pod: no cuentan los workers totales sino los ACTIVOS a la vez.
#      Los workers idle de FPM son forks que comparten memoria por copy-on-write,
#      asi que 'max_children x memory_limit' sobreestima muchisimo (hoy auth corre
#      static con 100 hijos en 1Gi). La regla util es:
#          memoria_pod >= (pico real por peticion x concurrencia_esperada) + 256MB
#      El pico real NO esta medido: medirlo con 'kubectl top pod' y pm.status_path
#      durante la prueba de carga, y ajustar desde el manifiesto.
#   2) Pool de PgBouncer: Laravel abre una conexion por peticion, asi que cada
#      worker ocupado consume una. NO es un presupuesto compartido: verificado en
#      el cluster (05/10/2026), cada servicio tiene su PROPIO cluster CNPG de una
#      instancia (max_connections=300) y su PROPIO PgBouncer con
#      default_pool_size=80 + reserve_pool=20, o sea ~100 conexiones utiles POR
#      SERVICIO. El limite real es ese pool, no max_connections.
#      El techo a respetar es entonces por servicio:
#          replicas x FPM_MAX_CHILDREN  <=  80
#      Con los defaults de abajo y 2 replicas sobra margen. (Nota: PgBouncer esta
#      en modo 'session' en sgd, mpv y casilla, y 'transaction' en auth y file;
#      'transaction' multiplexa mejor para PHP, que abre una conexion por peticion.)
#
#   3) CPU del pod: es el techo que de verdad manda hoy. Los pods tienen limite de
#      1 CPU (sgd, auth) y 500m (file, mpv, casilla). Mas workers que CPU solo
#      sirven para trabajo que espera I/O; para trabajo que quema CPU no agregan
#      capacidad, solo alargan la cola. En auth el login es CPU-bound (bcrypt con
#      cost 12, ~300 ms por intento), asi que con 1 CPU el techo son ~3 logins/s
#      por replica por mucho que se suba max_children: ahi lo que hay que subir
#      es la CPU, no los workers.
#
# Ojo: con pm=dynamic, FPM exige max_spare_servers <= max_children. Si subes
# FPM_MAX_CHILDREN sin mirar FPM_MAX_SPARE (o lo bajas por debajo), no arranca.
ENV FPM_PM=dynamic \
    FPM_MAX_CHILDREN=12 \
    FPM_START_SERVERS=3 \
    FPM_MIN_SPARE=2 \
    FPM_MAX_SPARE=6 \
    FPM_MAX_REQUESTS=500 \
    FPM_REQUEST_TIMEOUT=30s \
    FPM_BACKLOG=256 \
    PHP_MAX_EXECUTION_TIME=25 \
    PHP_MEMORY_LIMIT=256M

RUN cat > /usr/local/etc/php-fpm.d/zz-custom.conf <<'EOF'
[www]
pm = ${FPM_PM}
pm.max_children = ${FPM_MAX_CHILDREN}
pm.start_servers = ${FPM_START_SERVERS}
pm.min_spare_servers = ${FPM_MIN_SPARE}
pm.max_spare_servers = ${FPM_MAX_SPARE}
; Reciclar workers acota la fragmentacion de memoria del proceso
pm.max_requests = ${FPM_MAX_REQUESTS}

; Escalera de timeouts: PHP < FPM < nginx. PHP corta solo y deja traza en el log,
; FPM es el respaldo, y nginx nunca debe cortar primero. Antes se contradecian
; entre si (FPM mataba al worker antes de que PHP creyera haber agotado su tiempo,
; y nginx devolvia 502 por una respuesta truncada).
php_admin_value[max_execution_time] = ${PHP_MAX_EXECUTION_TIME}
php_admin_value[memory_limit] = ${PHP_MEMORY_LIMIT}
request_terminate_timeout = ${FPM_REQUEST_TIMEOUT}

; Cola de conexiones entrantes. Junto con fastcgi_connect_timeout de 5s en nginx,
; una peticion que no consigue worker falla rapido en vez de encolarse decenas de
; segundos inflando el P95 (era el patron de Auth: P95 de 65s con 0.5% de error).
listen.backlog = ${FPM_BACKLOG}

; Metricas del pool, para diagnosticar saturacion con datos y no por intuicion
pm.status_path = /fpm-status
EOF

# Límites de PHP
# OPcache + JIT optimizado para producción
RUN cat > /usr/local/etc/php/conf.d/zz-opcache-optimized.ini <<'EOF'
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 20000
opcache.revalidate_freq = 0
opcache.validate_timestamps = 0
opcache.fast_shutdown = 1
opcache.enable_file_override = 1
opcache.jit_buffer_size = 64M
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
