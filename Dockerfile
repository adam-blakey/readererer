# syntax=docker/dockerfile:1

# ---- Build frontend assets with Vite ----
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# ---- Install PHP dependencies (production only) ----
# Resolve/install against the same PHP the runtime uses (8.4) rather than
# whatever the composer:2 image currently ships, so platform requirements in
# composer.lock are checked against the real target. Pinned to the Debian
# bookworm variant to match the runtime image.
FROM php:8.4-cli-bookworm AS vendor
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
# git + unzip are needed for Composer to fetch/extract packages; unlike the
# Alpine composer image, php:8.4-cli does not bundle them.
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /app
COPY . .
RUN composer install \
        --no-dev \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader \
        --no-scripts

# ---- Runtime: Apache serving Laravel's public/ ----
# Pinned to the Debian bookworm variant: the unqualified php:8.4-apache tag now
# resolves to Debian 13 "trixie", which drops/renames some of the -dev packages
# installed below (e.g. libfreetype6-dev). Bookworm is the OS this apt recipe
# was written for and keeps every package name valid.
FROM php:8.4-apache-bookworm AS runtime

# System libraries and PHP extensions the app needs (dompdf -> gd, mysql/sqlite drivers, etc.)
RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        pdo_sqlite \
        mbstring \
        zip \
        gd \
        bcmath \
        exif \
    && rm -rf /var/lib/apt/lists/*

# Serve Laravel's public/ directory with .htaccess (mod_rewrite) enabled so the
# front controller handles routing.
RUN a2enmod rewrite \
    && { \
        echo '<VirtualHost *:80>'; \
        echo '    DocumentRoot /var/www/html/public'; \
        echo '    <Directory /var/www/html/public>'; \
        echo '        AllowOverride All'; \
        echo '        Require all granted'; \
        echo '    </Directory>'; \
        echo '    ErrorLog ${APACHE_LOG_DIR}/error.log'; \
        echo '    CustomLog ${APACHE_LOG_DIR}/access.log combined'; \
        echo '</VirtualHost>'; \
    } > /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# App source + installed vendor from the composer stage, then the built assets.
COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build

# Laravel needs these writable at runtime (package manifest, cache, logs, sessions).
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

EXPOSE 80
