ARG PHP_VERSION=8.0
FROM php:$PHP_VERSION-cli-alpine

RUN apk add git zip unzip autoconf make g++

# apparently newer xdebug needs these now?
RUN apk add --update linux-headers

RUN pecl install xdebug && docker-php-ext-enable xdebug

RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

WORKDIR /package

COPY composer.json ./

ARG LARAVEL=8
RUN composer require illuminate/support ^$LARAVEL.0

COPY src src
COPY tests tests
COPY phpunit.xml ./

RUN COMPOSER_ALLOW_SUPERUSER=1 composer test