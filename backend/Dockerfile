FROM composer:2.8 AS dependencias
WORKDIR /aplicacao
COPY composer.json composer.lock .//
RUN composer install --no-interaction --no-scripts --prefer-dist --ignore-platform-req=ext-redis

FROM php:8.3-cli-alpine AS desenvolvimento
RUN apk add --no-cache icu-dev libzip-dev postgresql-dev $PHPIZE_DEPS \
    && docker-php-ext-install intl opcache pdo_pgsql \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del postgresql-dev $PHPIZE_DEPS \
    && apk add --no-cache libpq
WORKDIR /aplicacao
COPY docker/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
COPY --from=dependencias /usr/bin/composer /usr/bin/composer
COPY --from=dependencias /aplicacao/vendor /aplicacao/vendor
COPY . .
RUN composer dump-autoload --classmap-authoritative
EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public", "public/index.php"]
