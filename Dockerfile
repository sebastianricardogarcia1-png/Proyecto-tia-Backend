FROM richarvey/nginx-php-fpm:latest

# Directorio de trabajo
WORKDIR /var/www/html

# Copiar el codigo fuente del proyecto (excluyendo secretos mediante .dockerignore)
COPY . /var/www/html

# Variables del contenedor para Laravel
ENV WEBROOT=/var/www/html/public
ENV SKIP_COMPOSER=1
ENV RUN_SCRIPTS=1
ENV PHP_ERRORS_STDERR=1
ENV LOG_CHANNEL=stderr
ENV COMPOSER_ALLOW_SUPERUSER=1

# Instalar dependencias optimizadas para produccion
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Asignar permisos a las carpetas de almacenamiento y cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Copiar y dar permisos de ejecucion a los scripts de inicio
COPY scripts/ /scripts/
RUN chmod +x /scripts/*.sh

# Render inyecta la variable $PORT y expone el puerto correspondiente
EXPOSE 10000
