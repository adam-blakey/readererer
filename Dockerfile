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

# Read by config/_version.php for the footer; .git is not in the build context,
# so the CI workflows compute these and pass them in.
ARG APP_VERSION_TAG=""
ARG APP_VERSION_HASH=""
ARG APP_VERSION_DATE=""
RUN printf '{"tag":"%s","hash":"%s","date":"%s"}\n' \
        "$APP_VERSION_TAG" "$APP_VERSION_HASH" "$APP_VERSION_DATE" \
        > version.json

RUN chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R ug+rwX storage bootstrap/cache database

# Puts application errors in `docker logs` rather than a file in the container.
ENV LOG_CHANNEL=stderr

COPY docker/entrypoint.sh /usr/local/bin/readererer-entrypoint
RUN chmod +x /usr/local/bin/readererer-entrypoint

ENTRYPOINT ["/usr/local/bin/readererer-entrypoint"]
CMD ["apache2-foreground"]

EXPOSE 80
