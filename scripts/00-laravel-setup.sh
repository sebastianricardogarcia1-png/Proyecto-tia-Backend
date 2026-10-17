#!/usr/bin/env bash
set -e

echo "=== Configurando Laravel para Render ==="

# 1. Configurar Nginx para escuchar en el puerto dinamico de Render ($PORT o 10000)
PORT="${PORT:-10000}"
echo "Configurando Nginx para escuchar en el puerto: $PORT..."

if [ -d "/etc/nginx" ]; then
    find /etc/nginx/ -type f -name "*.conf" -exec sed -i "s/listen 80;/listen ${PORT};/g" {} + 2>/dev/null || true
    find /etc/nginx/ -type f -name "*.conf" -exec sed -i "s/listen \[::\]:80;/listen [::]:${PORT};/g" {} + 2>/dev/null || true
fi

# 2. Crear el enlace simbolico de almacenamiento para fotos (public/storage -> storage/app/public)
echo "Verificando enlace simbolico de imagenes..."
php artisan storage:link --force || true

# 3. Optimizar configuracion y rutas si APP_KEY esta presente
if [ -n "$APP_KEY" ]; then
    echo "Optimizando configuracion de Laravel..."
    php artisan config:cache || true
    php artisan route:cache || true
fi

echo "=== Laravel listo para recibir peticiones ==="
