#!/bin/sh
set -e

if [ -z "${APP_KEY}" ] && ! grep -qs '^APP_KEY=.' .env; then
    echo "readererer: APP_KEY is not set; requests will fail until one is provided (php artisan key:generate --show)." >&2
fi

connection="${DB_CONNECTION:-$(sed -n 's/^DB_CONNECTION=//p' .env 2>/dev/null | tail -1)}"

# SQLite will not create its own file, and it writes journals beside it.
if [ "${connection:-sqlite}" = "sqlite" ]; then
    database="${DB_DATABASE:-/var/www/html/database/database.sqlite}"

    if [ ! -f "${database}" ]; then
        install -o www-data -g www-data -m 664 /dev/null "${database}" \
            || echo "readererer: could not create ${database}." >&2
    fi

    chown www-data:www-data "$(dirname "${database}")" "${database}" 2>/dev/null || true
fi

# Nothing here may stop Apache starting: a database that is unreachable or a
# migration that fails should surface per-request, not kill the container.
if [ "${SKIP_MIGRATIONS:-false}" != "true" ]; then
    php artisan migrate --force --no-interaction \
        || echo "readererer: migrations failed; starting anyway." >&2
fi

# artisan ran as root, so hand back anything it wrote.
chown -R www-data:www-data storage bootstrap/cache || true

# Without this, a container started with no command reaches `exec` with no
# arguments, which is a silent no-op that exits 0 before Apache ever runs.
if [ "$#" -eq 0 ]; then
    set -- apache2-foreground
fi

exec docker-php-entrypoint "$@"
