FROM wordpress:6.9.4-php8.4-apache

RUN apt-get update

RUN apt-get -y install libcurl4-openssl-dev
RUN docker-php-ext-install curl
RUN docker-php-ext-enable curl
