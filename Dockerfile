# WiseWallet 2.0 — PHP + Apache image used by the devcontainer / Codespaces
# preview. Not required for local XAMPP development.
FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev unzip git \
    && docker-php-ext-install pdo_mysql mbstring \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Apache should serve the public/ folder, with .htaccess honoured.
RUN sed -i 's#/var/www/html#/var/www/html/public#g' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\n  AllowOverride All\n  Require all granted\n</Directory>\n' \
       > /etc/apache2/conf-available/wisewallet.conf \
    && a2enconf wisewallet

WORKDIR /var/www/html
