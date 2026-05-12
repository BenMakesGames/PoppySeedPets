FROM php:8.4-fpm

# System packages: build tools for pecl, libs for PHP extensions, plus nginx for HTTPS termination
RUN apt-get update && apt-get install -y --no-install-recommends \
        $PHPIZE_DEPS \
        libicu-dev \
        libzip-dev \
        libonig-dev \
        unzip \
        git \
        nginx \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions required by composer.json (ext-* entries) and the Doctrine/Redis stack
RUN docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        intl \
        calendar \
        zip \
        opcache

# Redis extension comes from PECL
RUN pecl install redis \
    && docker-php-ext-enable redis

# Dev-tuned PHP settings (opcache + realpath_cache) — see php-dev.ini for rationale
COPY docker/api/php-dev.ini /usr/local/etc/php/conf.d/zz-psp-dev.ini

# Composer 2 from the official composer image
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Nginx site config — listens 8000 with TLS, FastCGIs to php-fpm on 127.0.0.1:9000
COPY docker/api/nginx.conf /etc/nginx/conf.d/psp.conf
RUN rm -f /etc/nginx/sites-enabled/default

# Entrypoint runs composer install, migrations, then fpm + nginx + crunz loop
COPY docker/api/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /app

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
