FROM composer:2 AS composer

FROM php:8.3-fpm-alpine

ARG UID=1000
ARG GID=1000

RUN apk add --no-cache icu-libs libzip git unzip \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev linux-headers \
    && docker-php-ext-install -j"$(nproc)" intl zip bcmath pcntl opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /tmp/pear

COPY --from=composer /usr/bin/composer /usr/local/bin/composer

RUN addgroup -g "$GID" app 2>/dev/null || true; \
    adduser -D -u "$UID" -G "$(getent group "$GID" | cut -d: -f1)" app \
    && sed -i 's/^user = .*/user = app/; s/^group = .*/group = app/' /usr/local/etc/php-fpm.d/www.conf

RUN { \
      echo 'opcache.enable=1'; \
      echo 'opcache.validate_timestamps=1'; \
      echo 'opcache.revalidate_freq=0'; \
    } > /usr/local/etc/php/conf.d/opcache-dev.ini

WORKDIR /var/www/backend

# php-fpm master stays root (required to drop to the pool user); `exec`/`run` use the app user.
