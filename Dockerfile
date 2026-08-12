FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev \
    && docker-php-ext-install pdo_mysql curl \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod headers rewrite

COPY . /var/www/html/

RUN mkdir -p /var/www/html/uploads/products \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 755 /var/www/html

ENV APACHE_DOCUMENT_ROOT=/var/www/html

EXPOSE 80
