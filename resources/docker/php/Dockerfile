FROM php:7.4-fpm

RUN apt-get update

RUN apt-get install -y \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && docker-php-ext-install bcmath \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

CMD ["php-fpm"]
