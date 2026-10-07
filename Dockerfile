FROM php:8.4-fpm-alpine

RUN apk add --no-cache $PHPIZE_DEPS icu-dev libzip-dev oniguruma-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl mbstring zip opcache \
    && apk del $PHPIZE_DEPS

WORKDIR /var/www/html
