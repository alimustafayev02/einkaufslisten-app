FROM php:8.2-fpm-alpine

# Systemabhängigkeiten installieren
RUN apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    bash

# PHP-Erweiterungen installieren
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli \
    zip \
    intl \
    opcache

# Composer installieren
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Arbeitsverzeichnis
WORKDIR /var/www/html

# Berechtigungen
RUN chown -R www-data:www-data /var/www/html

# Composer install beim Container-Start, dann Migrations ausführen, dann PHP-FPM
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
