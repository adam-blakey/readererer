#!/bin/sh
# Runtime bootstrap for the Readererer image.
#
# The image ships no .env and no database (both are excluded via
# .dockerignore), so a bare `docker run` used to 500 on every page:
# Laravel had no APP_KEY, and the default sqlite connection pointed at a
# database file that didn't exist and had never been migrated (sessions and
# cache live in the database too). This entrypoint fills those gaps so the
# dev/QA image runs out of the box, while still honouring any configuration
# passed in as environment variables.
set -e

cd /var/www/html

# Run artisan as www-data when we're root, so anything it creates (the
# sqlite database, compiled views, logs) stays writable by Apache's workers.
artisan() {
    if [ "$(id -u)" = "0" ]; then
        su -s /bin/sh www-data -c "php artisan $*"
    else
        php artisan "$@"
    fi
}

# APP_KEY is required to boot. Generate an ephemeral one if the server didn't
# supply it — fine for dev/QA, but sessions and encrypted data won't survive a
# container restart.
if [ -z "$APP_KEY" ]; then
    APP_KEY="$(php artisan key:generate --show)"
    export APP_KEY
    echo "readererer: APP_KEY not set — generated an ephemeral key." >&2
    echo "readererer: set APP_KEY (e.g. from 'php artisan key:generate --show') to keep sessions across restarts." >&2
fi

# The default connection is sqlite at database/database.sqlite (see
# config/database.php). Create the file if it doesn't exist yet so the first
# migrate has something to write to. DB_DATABASE, when set for sqlite, is an
# absolute path to the database file.
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    if [ ! -f "$DB_FILE" ]; then
        echo "readererer: creating sqlite database at $DB_FILE" >&2
        mkdir -p "$(dirname "$DB_FILE")"
        : > "$DB_FILE"
    fi
    # sqlite needs write on the directory too (journal/WAL files).
    if [ "$(id -u)" = "0" ]; then
        chown www-data:www-data "$DB_FILE" "$(dirname "$DB_FILE")"
    fi
fi

# Retry briefly so an external database (e.g. MySQL in the same compose
# stack) that is still starting up doesn't kill the container.
tries=0
until artisan migrate --force; do
    tries=$((tries + 1))
    if [ "$tries" -ge 10 ]; then
        echo "readererer: migrations still failing after $tries attempts, giving up." >&2
        exit 1
    fi
    echo "readererer: migrate failed (attempt $tries), retrying in 3s..." >&2
    sleep 3
done

# Opt-in sample data for a fresh dev/QA container. The seeders are not
# idempotent, so only set this on the first start against an empty database.
if [ "${APP_SEED:-false}" = "true" ] || [ "${APP_SEED:-0}" = "1" ]; then
    artisan db:seed --force
fi

exec "$@"
