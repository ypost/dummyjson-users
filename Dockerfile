FROM composer:latest AS composer

FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

RUN echo "memory_limit=-1" > /usr/local/etc/php/conf.d/99-development.ini

WORKDIR /app

CMD ["sleep", "infinity"]
