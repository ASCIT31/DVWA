# Supported PHP branch (8.4, security support until end of 2028). The floating
# 8.4 tag receives patch releases; the build fails if the image is older than
# 8.4.27, the first 8.4 release fixing PHP2Shell (CVE-2026-109618/109619).
FROM docker.io/library/php:8.4-apache

LABEL org.opencontainers.image.source=https://github.com/digininja/DVWA
LABEL org.opencontainers.image.description="DVWA pre-built image."
LABEL org.opencontainers.image.licenses="gpl-3.0"

WORKDIR /var/www/html

RUN php -r 'exit(version_compare(PHP_VERSION, "8.4.27", ">=") ? 0 : 1);' \
 || (echo "PHP $(php -r 'echo PHP_VERSION;') is too old: 8.4.27 or later is required" >&2 && exit 1)
COPY docker/php/zz-dvwa-security.ini /usr/local/etc/php/conf.d/zz-dvwa-security.ini

# https://www.php.net/manual/en/image.installation.php
RUN apt-get update \
 && export DEBIAN_FRONTEND=noninteractive \
 && apt-get install -y zlib1g-dev libpng-dev libjpeg-dev libfreetype6-dev iputils-ping git zip unzip 7zip  \
 && apt-get clean -y && rm -rf /var/lib/apt/lists/* \
 && docker-php-ext-configure gd --with-jpeg --with-freetype \
 && a2enmod rewrite \
 # Use pdo_sqlite instead of pdo_mysql if you want to use sqlite
 && docker-php-ext-install gd mysqli pdo pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data config/config.inc.php.dist config/config.inc.php

# This is configuring the stuff for the API
RUN cd /var/www/html/vulnerabilities/api \
 && composer install \
