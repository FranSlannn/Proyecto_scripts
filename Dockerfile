FROM php:7.4-cli
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

RUN apt-get update && \
     apt-get install -y \
         libzip-dev \
         && docker-php-ext-install zip