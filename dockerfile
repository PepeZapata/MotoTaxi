FROM php:8.5-apache

# Instalar extensiones necesarias de Linux y PHP para Laravel 13 y PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql zip

# Habilitar mod_rewrite para Apache (indispensable para las rutas de Laravel)
RUN a2enmod rewrite

# Apuntar el servidor Apache directamente a la carpeta public de Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Establecer directorio de trabajo y copiar el código
WORKDIR /var/www/html
COPY . .

# Descargar e instalar Composer de forma global en el contenedor
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Forzar la instalación omitiendo conflictos estrictos de plataforma local
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Configurar permisos para que Laravel pueda escribir logs y caché
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

CMD ["apache2-foreground"]
