#!/bin/sh
set -e

key_bytes() {
    case "$1" in
        base64:*) printf '%s' "${1#base64:}" | base64 -d 2>/dev/null | wc -c ;;
        *)        printf '%s' "$1" | wc -c ;;
    esac
}

app_key="${APP_KEY:-$(sed -n 's/^APP_KEY=//p' .env 2>/dev/null | tail -1 | tr -d '"')}"

# Laravel needs 16 or 32 bytes once any base64: prefix is decoded. A key that
# is merely non-empty is not enough: an unexpanded "$(...)" from a compose file
# reaches this point as a literal string and fails on every request.
case "$(key_bytes "${app_key}")" in
    16|32)
        ;;
    0)
        echo "readererer: APP_KEY is not set; requests will fail until one is provided." >&2
        ;;
    *)
        echo "readererer: APP_KEY is set but is not a usable key; requests will fail." >&2
        echo "readererer: run 'php artisan key:generate --show' and pass the base64: value it prints." >&2
        ;;
esac

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

# Dev: seeds every time.
# QA: seeds on flag.
# Prod: seeds via its own --seed_database.
if [ "${APP_SEED_DATABASE:-0}" = "1" ]; then
    php artisan db:seed --force \
        || echo "readererer: seeding failed; starting anyway." >&2
fi

# artisan ran as root, so hand back anything it wrote.
chown -R www-data:www-data storage bootstrap/cache || true

# Without this, a container started with no command reaches `exec` with no
# arguments, which is a silent no-op that exits 0 before Apache ever runs.
if [ "$#" -eq 0 ]; then
    set -- apache2-foreground
fi

exec docker-php-entrypoint "$@"
