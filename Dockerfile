FROM php:8.2-fpm

RUN apt-get update
RUN apt-get install -y libpq-dev curl
RUN apt-get install -y libcurl4-openssl-dev libzip-dev libonig-dev libpng-dev
RUN apt-get install -y libfreetype-dev libjpeg62-turbo-dev
RUN docker-php-ext-install curl zip mbstring pdo_pgsql pgsql
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

RUN mkdir -p /var/www

WORKDIR /var/www

COPY . /var/www

VOLUME /var/www

RUN composer update

RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash
RUN apt install -y symfony-cli

EXPOSE 8000

ENTRYPOINT ["symfony", "server:start"]