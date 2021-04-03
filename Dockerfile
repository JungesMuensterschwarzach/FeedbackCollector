FROM php:apache
RUN docker-php-ext-install mysqli
WORKDIR /var/www/html
COPY . ./