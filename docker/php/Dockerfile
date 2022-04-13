FROM php:8.0-fpm

RUN apt-get update

RUN apt-get install -y \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && docker-php-ext-install bcmath

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

CMD ["php-fpm"]
