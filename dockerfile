FROM php:8.5-apache

# Instalar extensiones necesarias de Linux y PHP para Laravel 13 y PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql zip pcntl

# Habilitar mod_rewrite para Apache (indispensable para las rutas de Laravel)
RUN a2enmod rewrite

# Apuntar el servidor Apache directamente a la carpeta public de Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Permitir que el .htaccess de Laravel (en public/) funcione: sin esto,
# Apache ignora las reglas de reescritura y cualquier ruta que no sea
# un archivo físico da 404 (por ejemplo, todo lo que empieza con /api/*).
RUN { \
    echo '<Directory ${APACHE_DOCUMENT_ROOT}>'; \
    echo '    Options Indexes FollowSymLinks'; \
    echo '    AllowOverride All'; \
    echo '    Require all granted'; \
    echo '</Directory>'; \
} > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

# Establecer directorio de trabajo y copiar el código
WORKDIR /var/www/html
COPY . .

# Descargar e instalar Composer de forma global en el contenedor
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Forzar la instalación omitiendo conflictos estrictos de plataforma local
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Configurar permisos para que Laravel pueda escribir logs y caché
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

CMD php artisan config:clear && php artisan migrate --force && apache2-foreground
