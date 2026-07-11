---
name: verify
description: Build, run, and drive Readererer locally to verify changes end-to-end.
---

# Verifying Readererer changes locally

## Setup (once per environment)

```bash
composer install
cp .env.example .env
# Point at SQLite (the .env.example defaults to MySQL):
#   DB_CONNECTION=sqlite
#   DB_DATABASE=/absolute/path/to/database/database.sqlite
# and comment out DB_HOST/DB_PORT/DB_USERNAME/DB_PASSWORD.
touch database/database.sqlite
php artisan key:generate
php artisan migrate:fresh --seed --force
npm install && npm run build
php artisan serve --host=127.0.0.1 --port=8099   # run in background
```

Gotcha: the Vite static-copy of Tabler icons can land in
`public/build/icons/node_modules/@tabler/icons/icons/outline/` instead of
`public/build/icons/` on some environments — if `<x-icon>` renders nothing,
flatten them: `cp public/build/icons/node_modules/@tabler/icons/icons/outline/*.svg public/build/icons/`.

## Logging in

Seeded logins (password `password` for all): usernames `guest`, `ensemble`,
`member`, `moderator`, `admin` — one per role. The 10 factory users also use
password `password`; find usernames/slugs with:

```bash
php artisan tinker --execute="echo App\Models\Ensemble::first()->slug;"
```

Login is username + password with CSRF. With curl:

```bash
TOKEN=$(curl -s -c jar http://127.0.0.1:8099/login | grep -oE 'name="_token" value="[^"]*"' | sed 's/.*value="//; s/"//')
curl -s -b jar -c jar --data-urlencode "_token=$TOKEN" --data-urlencode "username=admin" --data-urlencode "password=password" http://127.0.0.1:8099/login
```

Playwright (Chromium at `/opt/pw-browsers/chromium` on remote runners) works
for screenshots: fill `#username` / `#password`, click `button[type=submit]`,
wait for `**/dashboard`.

## Flows worth driving

- Dashboard: `/dashboard` (log in as a factory user who belongs to an ensemble,
  not `admin` — the role users have no ensembles).
- Attendance register (show): `/attendance/{ensemble-slug}/{term-slug}`.
- Attendance poll (edit): `/attendance/{ensemble-slug}/{term-slug}/edit`.
- Term page with per-date accordion: `/terms/{id}`.
- Ensemble page: `/ensembles/{id}` (moderator+ or a member of it).
