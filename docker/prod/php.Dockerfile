# Production PHP image. Build context: repository root.
FROM composer:2 AS vendor
WORKDIR /app
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts \
    --prefer-dist --optimize-autoloader
COPY backend/ ./
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-interaction

FROM php:8.3-fpm-alpine

RUN apk add --no-cache icu-libs libzip \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev linux-headers \
    && docker-php-ext-install -j"$(nproc)" intl zip bcmath opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /tmp/pear

RUN { \
      echo 'opcache.enable=1'; \
      echo 'opcache.validate_timestamps=0'; \
      echo 'opcache.memory_consumption=128'; \
      echo 'opcache.max_accelerated_files=20000'; \
      echo 'expose_php=0'; \
      echo 'display_errors=0'; \
    } > /usr/local/etc/php/conf.d/zz-prod.ini \
    && sed -i 's/^user = .*/user = www-data/; s/^group = .*/group = www-data/' /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/backend
# Code stays root-owned (read-only for the runtime user); only storage and bootstrap/cache are writable.
COPY --from=vendor /app ./
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/prod/php-entrypoint.sh /usr/local/bin/php-entrypoint
RUN chmod +x /usr/local/bin/php-entrypoint

# Entrypoint runs as the unprivileged user; php-fpm is started from a non-root master
# (its pool user setting is then ignored), so the whole container is non-root.
USER www-data
EXPOSE 9000
ENTRYPOINT ["php-entrypoint"]
CMD ["php-fpm"]
