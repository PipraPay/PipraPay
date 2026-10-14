FROM php:8.2-apache

LABEL maintainer="PipraPay Docker"

RUN apt-get update && apt-get install -y \
    default-mysql-client \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libmagickwand-dev \
    libcurl4-openssl-dev \
    libonig-dev \
    libxml2-dev \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure zip \
    && pecl install imagick && docker-php-ext-enable imagick \
    && docker-php-ext-install -j$(nproc) bcmath curl gd fileinfo mbstring zip pdo pdo_mysql xml \
    && a2enmod rewrite headers expires deflate \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
