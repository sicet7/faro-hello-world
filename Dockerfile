FROM php:8.1-cli-alpine AS builder

WORKDIR /build

ADD https://getcomposer.org/download/latest-2.x/composer.phar /usr/local/bin/composer
ADD ./ /build/

RUN chmod +x /usr/local/bin/composer && \
    composer install --no-dev && \
    php build.php

FROM openswoole/swoole:4.10-php8.1

WORKDIR /var/www

ADD https://github.com/mlocati/docker-php-extension-installer/releases/download/1.5.8/install-php-extensions /usr/local/bin/
COPY --from=builder /build/faro.phar /var/www/faro.phar

RUN apk add --no-cache openssl-dev libzip-dev libpq-dev && \
    apk add --update --no-cache --virtual buildDeps \
        autoconf \
        gcc \
        make \
        libxml2-dev \
        curl \
        tzdata \
        curl-dev \
        oniguruma-dev \
        g++ && \
    pecl install mongodb && \
    docker-php-ext-install pdo mysqli pdo_mysql bcmath ftp zip pcntl pdo_pgsql && \
    docker-php-ext-enable mongodb && \
    chmod +x /usr/local/bin/install-php-extensions && \
    sync && \
    install-php-extensions redis-stable
