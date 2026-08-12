FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql

RUN a2enmod headers

COPY . /var/www/html/

RUN mkdir -p /var/www/html/uploads/products \
    && chown -R www-data:www-data /var/www/html/uploads

EXPOSE 80
