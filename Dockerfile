FROM openswoole/swoole:4.10-php8.1

WORKDIR /var/www

ADD https://github.com/mlocati/docker-php-extension-installer/releases/download/1.5.8/install-php-extensions /usr/local/bin/
ADD . /var/www/

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
    composer install --no-dev && \
    rm -rf /var/www/lib && \
    install-php-extensions redis-stable && \
    mv /var/www/entrypoint.sh /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
