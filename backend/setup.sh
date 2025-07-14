#!/bin/bash

echo "🔧 Creando carpetas necesarias..."
mkdir -p bootstrap/cache
mkdir -p storage/framework/{cache/data,sessions,views}
chmod -R 775 bootstrap/cache storage
chown -R www-data:www-data storage/

echo "🔧 Configurando entorno de Laravel..."

# Instalar dependencias
composer install

# Copiar .env si no existe
if [ ! -f .env ]; then
  cp .env.example .env
  echo "📄 Archivo .env copiado desde .env.example"
fi

# Generar clave de aplicación
php artisan key:generate
echo "🔑 Clave de aplicación generada"

# Esperar a que MySQL esté listo
# echo "⏳ Esperando a la base de datos..."
# until php artisan migrate:status > /dev/null 2>&1
# do
#   echo "  ⌛ Aún esperando a la BD..."
#   sleep 2
# done

# Ejecutar migraciones
php artisan migrate --seed --force
echo "✅ Migraciones ejecutadas"

# Instalar Passport
# php artisan passport:install
# echo "🔐 Passport instalado"

echo "🎉 Laravel listo para trabajar"
