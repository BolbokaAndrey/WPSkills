FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
        icu-dev libzip-dev oniguruma-dev \
        libpng-dev libjpeg-turbo-dev freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        gd intl mbstring mysqli opcache pdo_mysql zip

ARG APP_UID=1000
ARG APP_GID=1000

RUN addgroup -g "$APP_GID" app \
    && adduser -D -u "$APP_UID" -G app app \
    && sed -i \
        -e 's/^user = www-data$/user = app/' \
        -e 's/^group = www-data$/group = app/' \
        /usr/local/etc/php-fpm.d/www.conf

COPY session.ini /usr/local/etc/php/conf.d/session.ini

WORKDIR /var/www/html
