# syntax=docker/dockerfile:1

# ---- Build frontend assets with Vite ----
FROM node:24-alpine AS assets
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
# Pinned to the Debian bookworm variant for a reproducible base: the unqualified
# php:8.4-apache tag now floats to Debian 13 "trixie", and pinning keeps the OS
# (and therefore the apt package set below) stable across rebuilds.
FROM php:8.4-apache-bookworm AS runtime

# System libraries and PHP extensions the app needs (dompdf -> gd, mysql/sqlite drivers, etc.)
RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libonig-dev \
        libsqlite3-dev \
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

# Version metadata shown in the app footer (read by config/_version.php).
# .git is excluded from the build context, so the CI workflows compute these
# from git and pass them in as build arguments; the defaults leave the file
# empty and the config falls back to its placeholders.
ARG APP_VERSION_TAG=""
ARG APP_VERSION_HASH=""
ARG APP_VERSION_DATE=""
RUN printf '{"tag":"%s","hash":"%s","date":"%s"}\n' \
        "$APP_VERSION_TAG" "$APP_VERSION_HASH" "$APP_VERSION_DATE" \
        > version.json

# Laravel needs these writable at runtime (package manifest, cache, logs,
# sessions). database/ is included because the default sqlite connection
# stores its database (and journal files) there.
RUN chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R ug+rwX storage bootstrap/cache database

# Log to the container's stderr by default so application errors show up in
# `docker logs`; override LOG_CHANNEL to change.
ENV LOG_CHANNEL=stderr

# The entrypoint generates an APP_KEY when none is supplied, creates the
# sqlite database if needed, and runs migrations before starting Apache —
# without it every request 500s on a bare `docker run`.
COPY docker/entrypoint.sh /usr/local/bin/readererer-entrypoint
RUN chmod +x /usr/local/bin/readererer-entrypoint

EXPOSE 80

ENTRYPOINT ["readererer-entrypoint"]
CMD ["apache2-foreground"]
