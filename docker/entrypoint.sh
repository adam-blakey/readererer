#!/bin/sh
set -e

if [ -z "${APP_KEY}" ]; then
    echo "readererer: APP_KEY is not set; pass one in (php artisan key:generate --show)." >&2
    exit 1
fi

# SQLite will not create its own file, and it writes journals beside it.
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    database="${DB_DATABASE:-/var/www/html/database/database.sqlite}"

    if [ ! -f "${database}" ]; then
        install -o www-data -g www-data -m 664 /dev/null "${database}"
    fi

    chown www-data:www-data "$(dirname "${database}")" "${database}"
fi

if [ "${SKIP_MIGRATIONS:-false}" != "true" ]; then
    php artisan migrate --force --no-interaction
fi

# artisan ran as root, so hand back anything it wrote.
chown -R www-data:www-data storage bootstrap/cache

exec docker-php-entrypoint "$@"
