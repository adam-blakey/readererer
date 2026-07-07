# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Readererer is a Laravel 11 (PHP 8.2) app for managing a music ensemble/orchestra: members, ensembles, terms and rehearsal/concert dates, attendance polls, setlists/pieces/composers, seating plans, and setup-group/van-driver rosters. Frontend is Blade + Tabler UI + Tailwind, bundled with Vite. Database is SQLite (`database/database.sqlite`).

## Commands

```bash
# Install
composer install
npm install

# Run dev (serve PHP + Vite in separate terminals)
php artisan serve
npm run dev

# Build frontend assets
npm run build

# Database
php artisan migrate
php artisan migrate:fresh --seed   # rebuild + seed sample data
php artisan db:seed

# Tests (Pest)
php artisan test
./vendor/bin/pest
./vendor/bin/pest tests/Feature/EndpointAccessTest.php          # single file
./vendor/bin/pest --filter 'name of test'                        # single test

# Lint / format (Laravel Pint)
./vendor/bin/pint
./vendor/bin/pint --test    # check only, no changes
```

Tests run with `APP_ENV=testing` (see `phpunit.xml`); the SQLite-in-memory env lines are currently commented out, so tests hit the configured DB connection.

## Deployment

Deploys are GitLab-CI driven (`.gitlab-ci.yml`) via Laravel Envoy (`Envoy.blade.php`), SSH to a Krystal host. Pushing tags (`v*`) deploys `main` to the demo server; the `develop` branch deploys to the test server. The Envoy `deploy` story does git reset, npm install, asset compile, composer install, `migrate`, optionally seed, then brings the app back up. Do not invoke deploys yourself.

## Architecture

### Convention-driven generic CRUD ("auto-entities")
Most entities (Composer, Piece, Setlist, Term, etc.) are rendered by shared views in `resources/views/auto-entities/` (`index`, `show`, `form`) rather than per-entity Blade. The forms are built dynamically by reflecting over the model:

- `app/helpers.php` (globally autoloaded via composer `files`) — `get_create_fields()` introspects a model's `getFillable()`, DB column types (`Schema::getColumns`), casts, and relationships to produce a field list (label, html input type, required, icon, options) for the generic form. `map_database_type_to_html()` maps DB types to form inputs. `get_route_name_from_model()` / `get_class_name_from_model()` derive route names like `composers.show` from a model instance.
- `App\Attributes\Icon` + `App\Traits\HasPropertyIcons` — annotate a model relation method with `#[Icon('name')]`, or (for database attributes) the model class with `#[Icon('name', for: 'attribute')]`, and the form/show views pull the Tabler icon for that attribute via `getIconForAttribute()`. Never declare a real property for a database column just to carry an annotation — it shadows Eloquent's attribute handling (breaking soft deletes, restores and timestamps).

When adding a field to an entity, update the migration **and** the model's `$fillable` (and `$casts`/`$visible`/`$sortables` as needed); the generic form picks it up automatically. There is a `// TODO: enum` gap — enum columns are not yet mapped to form inputs.

### Authorization
Role-based via the `UserRole` int enum (`Guest=0, Ensemble=1, Member=2, Moderator=3, Admin=4`) on `users.role`. Policies in `app/Policies/` compare `$user->role->value >= UserRole::X->value`. Controllers call `$this->authorizeResource(Model::class)` in their constructor, and routes attach `->can(...)` / `->middleware('auth')` (see `routes/web.php`). The `Ensemble` role is a shared generic login that can only update attendance polls; `view` on an ensemble also allows non-admins who belong to that ensemble (`$user->ensembles->contains(...)`).

### Soft deletes everywhere
Models use `SoftDeletes`. Index controllers honour a `with_trashed` query param, and each resource has a `restore` route (`PATCH /{resource}/{id}/restore`) plus `purgeTrashed`.

### Sorting
Uses `s-damian/larasort` (`AutoSortable` trait + `$sortables` array on models). Index queries call `->autosort()`; the `<x-larasort-link>` Blade component renders sortable column headers.

### Attendance model
`member_status_totals()` in `helpers.php` computes attending/not-attending/unknown counts for a term date, taking the latest attendance record per member. Behaviour is tuned by custom config keys in `config/app.php`: `readererer_assume_attending`, `readererer_allow_change_to_unknown`, `readererer_repeating_headings` (env-overridable). `AttendanceStatus` enum is `Unknown=0, Attending=1, NotAttending=2`.

### Seating plans & PDFs
`SeatingPlanController` edits per-ensemble seating (seat_row/seat_column stored on the `user_ensemble` pivot). PDF output uses `barryvdh/laravel-dompdf` (`SeatingPlanPdfController`, `seating-plan.download` route).

### Key domain relationships
- `Term` hasMany `TermDate`; a `TermDate` with `ensemble_id = null` is a rehearsal, otherwise it's that ensemble's concert (see `ShowEnsemble` trait). Terms cache a `latest_date`.
- `User` belongsToMany `Ensemble` through `user_ensemble` (pivot carries `instrument_family_id`, `seat_row`, `seat_column`); belongsTo `SetupGroup`.
- `Setlist` ↔ `Piece` via `SetlistPiece`; `Piece` belongsTo `Composer`, hasMany `Part` (each part tied to an `InstrumentFamily`).

### Frontend
Blade components live in `resources/views/components/` (Tabler-based: `card`, `table`, `avatar`, `setup-group-badge`, form partials under `components/forms/`). JS/CSS entrypoints are wired through `vite.config.js` + `@tabler/core`. Colours use Tabler names; `color_name_to_hex()` in `helpers.php` maps them to hex for inline styling/PDFs.

## Conventions

- `Model::preventSilentlyDiscardingAttributes()` is enabled in local env (`AppServiceProvider`) — mass-assignment of non-fillable attributes throws, so keep `$fillable` accurate.
- Helper functions in `app/helpers.php` are global (snake_case); Blade leans on them heavily. Check there before writing new view-logic helpers.
- `docs/development-plan.md` is the running TODO / priorities list — consult it for intended direction and known bugs (e.g. seating-plan row bugs, missing edit views).
- Tabler icons are referenced by name through the `<x-icon>` component / `Icon` attribute, not raw SVG.
