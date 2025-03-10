FROM wordpress:6.7.2-php8.3-apache

RUN apt-get update

RUN apt-get -y install libcurl4-openssl-dev
RUN docker-php-ext-install curl
RUN docker-php-ext-enable curl

RUN pecl install "xdebug" 
RUN docker-php-ext-enable xdebug
RUN echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

CMD chmod a+rw -R /usr/src/wordpress && docker-entrypoint.sh apache2-foreground
