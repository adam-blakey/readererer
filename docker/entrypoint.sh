#!/bin/sh
#
# Prepares the container before handing off to Apache.
#
# No .env file is baked into the image (.dockerignore excludes it), so all
# configuration arrives as container environment variables — Laravel reads
# those through getenv(). That leaves two things which have to be true before
# the first request is served, and both surface as an opaque HTTP 500 with
# nothing in `docker logs` if they are not:
#
#   1. APP_KEY must be set, or encrypting the session cookie throws.
#   2. The database must exist and be migrated, because the default session
#      and cache stores are both `database`, so every request touches it.
set -e

if [ -z "${APP_KEY}" ]; then
    echo "readererer: APP_KEY is not set — the app cannot encrypt cookies or sessions." >&2
    echo "readererer: generate one with 'php artisan key:generate --show' and pass it in," >&2
    echo "readererer: e.g. docker run -e APP_KEY=base64:... ..." >&2
    exit 1
fi

# SQLite (the default connection) will not create its own database file, and
# it writes journal files alongside it, so the directory needs to be writable
# by the Apache user too. Mount a volume over the directory to persist data
# across container replacements.
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    database="${DB_DATABASE:-/var/www/html/database/database.sqlite}"

    if [ ! -f "${database}" ]; then
        echo "readererer: creating SQLite database at ${database}"
        install -o www-data -g www-data -m 664 /dev/null "${database}"
    fi

    chown www-data:www-data "$(dirname "${database}")" "${database}"
fi

# The image ships the migrations but no schema; without the tables even a
# request to a public page fails on the session lookup.
if [ "${SKIP_MIGRATIONS:-false}" != "true" ]; then
    php artisan migrate --force --no-interaction
fi

# artisan runs as root here, so hand anything it wrote (the package manifest,
# compiled views, logs) back to Apache before dropping into the server.
chown -R www-data:www-data storage bootstrap/cache

exec docker-php-entrypoint "$@"
