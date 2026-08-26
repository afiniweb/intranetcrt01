FROM composer:2.8 AS dependencias_desenvolvimento
WORKDIR /aplicacao
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --prefer-dist --ignore-platform-req=ext-redis

FROM composer:2.8 AS dependencias_producao
WORKDIR /aplicacao
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --classmap-authoritative --ignore-platform-req=ext-redis

FROM php:8.3-fpm-alpine AS runtime
RUN apk add --no-cache icu-dev libzip-dev postgresql-dev $PHPIZE_DEPS \
    && docker-php-ext-install intl opcache pdo_pgsql \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del postgresql-dev $PHPIZE_DEPS \
    && apk add --no-cache libpq
WORKDIR /aplicacao
COPY docker/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

FROM runtime AS desenvolvimento
COPY --from=dependencias_desenvolvimento /usr/bin/composer /usr/bin/composer
COPY --from=dependencias_desenvolvimento /aplicacao/vendor /aplicacao/vendor
COPY . .
RUN composer dump-autoload --classmap-authoritative
EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public", "public/index.php"]

FROM runtime AS producao
ENV APP_ENV=prod APP_DEBUG=0
COPY --from=dependencias_producao /aplicacao/vendor /aplicacao/vendor
COPY --from=dependencias_producao /usr/bin/composer /usr/bin/composer
COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative --no-scripts \
    && rm /usr/bin/composer \
    && mkdir -p var/cache var/log var/uploads/publicacoes \
    && chown -R www-data:www-data var
USER www-data
EXPOSE 9000
CMD ["php-fpm", "-F"]
