# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Readererer is a Laravel 13 (PHP 8.4) app for managing a music ensemble/orchestra: members, ensembles, terms and rehearsal/concert dates, attendance polls, setlists/pieces/composers, seating plans, and setup-group/van-driver rosters. Frontend is Blade + Tabler UI + Tailwind, bundled with Vite. Database is SQLite (`database/database.sqlite`).

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

Tests run with `APP_ENV=testing` against an in-memory SQLite database (see `phpunit.xml`), so they never touch
`database/database.sqlite`. `tests/Unit/IconComponentTest.php` asserts against `public/build/icons/*.svg`, so
the suite needs `npm run build` to have been run at least once — without it, 9 tests fail on a clean checkout.

## Deployment

Deploys are GitHub-Actions driven (`.github/workflows/`, documented in `.github/DEPLOYMENTS.md`). Pull requests
build a Docker image tagged `dev` (`deploy-dev.yml`); pushes to `main` build the QA image (`deploy-qa.yml`);
publishing a GitHub release deploys production over SSH with Laravel Envoy (`deploy-production.yml`, running the
`deploy` story in `Envoy.blade.php`). Do not invoke deploys yourself.

## Architecture

### Convention-driven generic CRUD ("auto-entities")
Most entities (Composer, Piece, Setlist, Term, etc.) are rendered by shared views in `resources/views/auto-entities/` (`index`, `show`, `form`) rather than per-entity Blade. The forms are built dynamically by reflecting over the model:

- `app/helpers.php` (globally autoloaded via composer `files`) — `get_create_fields()` introspects a model's `getFillable()`, DB column types (`Schema::getColumns`), casts, and relationships to produce a field list (label, html input type, required, icon, options) for the generic form. `map_database_type_to_html()` maps DB types to form inputs. `get_route_name_from_model()` / `get_class_name_from_model()` derive route names like `composers.show` from a model instance.
- `App\Attributes\Icon` + `App\Traits\HasPropertyIcons` — annotate a model relation method with `#[Icon('name')]`, or (for database attributes) the model class with `#[Icon('name', for: 'attribute')]`, and the form/show views pull the Tabler icon for that attribute via `getIconForAttribute()`. Never declare a real property for a database column just to carry an annotation — it shadows Eloquent's attribute handling (breaking soft deletes, restores and timestamps).

When adding a field to an entity, update the migration **and** the model's `$fillable` (and `$casts`/`$visible`/`$sortables` as needed); the generic form picks it up automatically. Enum columns are driven by the model's enum cast: `get_enum_class_for_attribute()` spots it, and the form renders a select of the enum's cases (labelled by the enum's own `label()` method where it has one, otherwise by the humanised case name) defaulting to the column's database default.

### Authorization
Role-based via the `UserRole` int enum (`Guest=0, Ensemble=1, Member=2, Moderator=3, Admin=4`) on `users.role`. Policies in `app/Policies/` compare `$user->role->value >= UserRole::X->value`. Controllers call `$this->authorizeResource(Model::class)` in their constructor, and routes attach `->can(...)` / `->middleware('auth')` (see `routes/web.php`). The `Ensemble` role is a shared generic login that can only update attendance polls; `view` on an ensemble also allows non-admins who belong to that ensemble (`$user->ensembles->contains(...)`).

### Soft deletes everywhere
Models use `SoftDeletes`. Index controllers honour a `with_trashed` query param, and each resource has a `restore` route (`PATCH /{resource}/{id}/restore`) plus `purgeTrashed`.

### Sorting
Uses `s-damian/larasort` (`AutoSortable` trait + `$sortables` array on models). Index queries call `->autosort()`; the `<x-larasort-link>` Blade component renders sortable column headers.

### Attendance model
`member_status_totals()` in `helpers.php` computes attending/not-attending/unknown counts for a term date, taking the latest attendance record per member. Behaviour is tuned by custom config keys in `config/app.php`: `readererer_assume_attending`, `readererer_allow_change_to_unknown`, `readererer_repeating_headings` (env-overridable). `AttendanceStatus` enum is `Unknown=0, Attending=1, NotAttending=2`.

### Attendance register
The poll records what members said they *would* do and is append-only; the register (`RegisterEntry`, `AttendanceRegisterController`, `attendance.register.*` routes) records what actually happened on the day and holds one row per member per date per ensemble, updated in place (unique index on `term_date_id + ensemble_id + user_id`). `RegisterStatus` is `Unmarked=0, Present=1, Absent=2, Late=3`; clearing a member back to `Unmarked` with no note deletes their row. `register_status_totals()` in `helpers.php` counts a register, treating members with no row as unmarked. A register only exists for dates that apply to the ensemble — rehearsals apply to everyone, a concert only to the ensemble playing it (`TermDate::appliesToEnsemble()`).

### Seating plans & PDFs
`SeatingPlanController` edits per-ensemble seating (seat_row/seat_column stored on the `user_ensemble` pivot). PDF output uses `barryvdh/laravel-dompdf` (`SeatingPlanPdfController`, `seating-plan.download` route).

### Key domain relationships
- `Term` hasMany `TermDate`; a `TermDate` with `ensemble_id = null` is a rehearsal, otherwise it's that ensemble's concert (see `ShowEnsemble` trait). Terms cache a `latest_date`.
- `User` belongsToMany `Ensemble` through `user_ensemble` (pivot carries `instrument_family_id`, `seat_row`, `seat_column`); belongsTo `SetupGroup`.
- `Setlist` ↔ `Piece` via `SetlistPiece`; `Piece` belongsTo `Composer`, hasMany `Part` (each part tied to an `InstrumentFamily`).

### Frontend
Blade components live in `resources/views/components/` (Tabler-based: `card`, `table`, `avatar`, `setup-group-badge`, form partials under `components/forms/`). JS/CSS entrypoints are wired through `vite.config.js` + `@tabler/core`. Colours use Tabler names; `color_name_to_hex()` in `helpers.php` maps them to hex for inline styling/PDFs.

### Breadcrumbs
`diglactic/laravel-breadcrumbs` renders the trail in `components/page-header.blade.php`, which is only reached when the layout's `show_page_header` is true. Trails are defined per route name in `routes/breadcrumbs.php` — the generic CRUD resources share one definition that registers their `index`/`create`/`show`/`edit` trails, labelling records by the model's `name` attribute. Add a definition there whenever you add a named GET page; `BreadcrumbsTest` fails if one is missing. Markup lives in `resources/views/vendor/breadcrumbs/tabler.blade.php` (selected by `config/breadcrumbs.php`).

## Conventions

- `Model::preventSilentlyDiscardingAttributes()` is enabled in local env (`AppServiceProvider`) — mass-assignment of non-fillable attributes throws, so keep `$fillable` accurate.
- Helper functions in `app/helpers.php` are global (snake_case); Blade leans on them heavily. Check there before writing new view-logic helpers.
- `docs/todos.md` is the running TODO / priorities list, split by delivery phase — consult it for intended
  direction and known bugs. `docs/rough-edges-triage.md` measures the cross-cutting rough edges (generic-form
  error handling, responsive text, test/CI gaps) and sizes the follow-up work.
- Tabler icons are referenced by name through the `<x-icon>` component / `Icon` attribute, not raw SVG.
