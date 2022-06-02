FROM php:8.0-fpm

RUN apt-get update -y

RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN docker-php-ext-install bcmath
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug

RUN apt-get install -y libpng-dev libfreetype6-dev libjpeg62-turbo-dev libgd-dev libpng-dev
RUN docker-php-ext-configure gd \
       --with-freetype=/usr/include/ \
       --with-jpeg=/usr/include/
RUN docker-php-ext-install gd

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

CMD ["php-fpm"]
