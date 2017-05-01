FROM php:5-apache
COPY . /var/www/html/
RUN docker-php-ext-install mysql
WORKDIR /var/www/html/
