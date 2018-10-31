FROM php:7.2.4-apache

COPY apache_default /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

COPY src /var/www/html/src
COPY public /var/www/html/public
COPY config /var/www/html/config
ADD composer.json /var/www/html
ADD composer.lock /var/www/html

# Install software
RUN apt-get update && apt-get install -y git
# Install unzip
RUN apt-get install -y unzip
# Install curl
RUN apt-get install -y curl

# Install dependencies
RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer

RUN cd /var/www/html && composer install --no-dev --no-interaction --optimize-autoloader
# install pdo for mysql
RUN docker-php-ext-install pdo pdo_mysql

COPY "memory-limit-php.ini" "/usr/local/etc/php/conf.d/memory-limit-php.ini"

RUN chmod 777 -R /var/www

EXPOSE 80

CMD apachectl -D FOREGROUND
