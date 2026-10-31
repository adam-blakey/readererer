# syntax=docker/dockerfile:1

FROM node:24-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# Installs on the same PHP the runtime uses, so composer.lock's platform
# requirements are checked against the real target rather than composer:2's PHP.
FROM php:8.4-cli-bookworm AS vendor
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
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

RUN php artisan package:discover --ansi

# Pinned to bookworm because the unqualified php:8.4-apache tag floats to trixie.
FROM php:8.4-apache-bookworm AS runtime

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

# Suppresses the AH00558 warning; a container has no hostname to resolve.
RUN echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

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

COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build

# Read by config/app.php for the footer; .git is not in the build context.
ARG APP_VERSION=""
RUN if [ -n "$APP_VERSION" ]; then echo "$APP_VERSION" > VERSION; fi

RUN chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R ug+rwX storage bootstrap/cache database

# Puts application errors in `docker logs` rather than a file in the container.
ENV LOG_CHANNEL=stderr

ARG APP_SEED_DATABASE=0
ENV APP_SEED_DATABASE=${APP_SEED_DATABASE}

COPY docker/entrypoint.sh /usr/local/bin/readererer-entrypoint
RUN chmod +x /usr/local/bin/readererer-entrypoint

ENTRYPOINT ["/usr/local/bin/readererer-entrypoint"]
CMD ["apache2-foreground"]

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl --fail --silent --show-error http://localhost/health || exit 1

EXPOSE 80
