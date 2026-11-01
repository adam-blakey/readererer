# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Readererer is a Laravel 13 (PHP 8.5) app for managing a music ensemble/orchestra: members, ensembles, terms and rehearsal/concert dates, attendance polls, setlists/pieces/composers, seating plans, and setup-group/van-driver rosters. Frontend is Blade + Tabler UI, bundled with Vite. Database is SQLite (`database/database.sqlite`).

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

# Serve uploaded images (once per checkout)
php artisan storage:link

# Database
php artisan migrate
php artisan migrate:fresh --seed   # rebuild + seed sample data
php artisan db:seed

# Tests (Pest)
php artisan test
./vendor/bin/pest
./vendor/bin/pest tests/Feature/EndpointAccessTest.php          # single file
./vendor/bin/pest --filter 'name of test'                        # single test
./vendor/bin/pest --testsuite=Unit,Feature                       # skip browser tests

# Lint / format (Laravel Pint)
./vendor/bin/pint
./vendor/bin/pint --test    # check only, no changes

# Laravel Boost (dev-only MCP server in .mcp.json; runs when APP_ENV=local or APP_DEBUG=true)
php artisan boost:update    # refresh .claude/skills; guidelines are off in boost.json so this file stays the only project instructions
```

Tests run with `APP_ENV=testing` (see `phpunit.xml`); the SQLite-in-memory env lines are currently commented out, so tests hit the configured DB connection.

Browser tests (`tests/Browser`) use `pestphp/pest-plugin-browser` to check that elements are on the page and clickable in a real browser (`visit('/login')->type(...)->click(...)`). Unlike the other suites they load the real Vite assets, so they need `npm run build` and `npx playwright install` first.

## Deployment

All environments (dev, qa and the production sites prod and nsw-beta) are on Krystal and deployed by GitHub Actions: `.github/workflows/deploy.yml` is a reusable workflow (build, test, deploy to dev) called by `pull-request.yml` for pull requests and by `main.yml` for pushes to `main`, which adds the qa, release, production and docs jobs, so pull requests show no skipped release jobs. Dev and qa deploy through the composite action `.github/actions/deploy-ssh`, the production sites through `.github/actions/deploy-cpanel` and cPanel Git™ Version Control: pull requests deploy to dev; pushes to `main` deploy to dev, then qa, then (after the `release` environment's wait timer, in `release_tag`, which pushes the release tag) every production site at once in the `deploy_prod` matrix, then `release` publishes the GitHub release. After them, the `docs` job rsyncs the static site in `site/` to `readererer.blakey.family`; the production domains are `readererer-prod.blakey.family` (prod) and `attendance-beta.nsw.org.uk` (nsw-beta). For dev and qa the workflow builds `vendor` and `public/build` on the runner, rsyncs them over SSH, resets the server's checkout to the commit and migrates (dev is `migrate:fresh --seed`). The production sites have no SSH: each matrix job calls the cPanel API to pull `main` into the account's clone, which is the site's domain folder itself, and run the tasks in `.cpanel.yml` (which derives the site from the clone's path, so one file serves every site), then polls until the deployment finishes. Those tasks build `vendor` and the assets in `~/readererer-build/<domain>`, then put the app in maintenance mode, rsync them into the domain folder, migrate, re-cache and bring it back up. The host disables `proc_open`, so Composer runs with `--no-scripts` and `package:discover` is its own task. The host also caps virtual memory (address space, not RAM), which a default Vite build exceeds many times over: V8 reserves up to 10 GB per WebAssembly memory and the allocators reserve arenas per thread. So the tasks run Vite through `node --disable-wasm-trap-handler --wasm-max-mem-pages=4096` (`NODE_OPTIONS` rejects that V8 flag) with the allocator and thread-count variables exported beforehand, which builds under a 2 GB cap (the host's is 4 GB). `artisan down` is skipped while the site's folder has no `vendor`, as on a site's first deploy. `release_tag` pushes the tag before deploying so the server's `git describe` can write it to `VERSION`. Setup and required repo settings are in `.github/DEPLOYMENTS.md`. Do not invoke deploys yourself.

## Architecture

### Convention-driven generic CRUD ("auto-entities")
Most entities (Composer, Piece, Setlist, Term, etc.) are rendered by shared views in `resources/views/auto-entities/` (`index`, `show`, `form`) rather than per-entity Blade. The forms are built dynamically by reflecting over the model:

- `app/helpers.php` (globally autoloaded via composer `files`) — `get_create_fields()` introspects a model's `getFillable()`, DB column types (`Schema::getColumns`), casts, and relationships to produce a field list (label, html input type, required, icon, options) for the generic form. `map_database_type_to_html()` maps DB types to form inputs. `get_route_name_from_model()` / `get_class_name_from_model()` derive route names like `composers.show` from a model instance.
- `App\Attributes\Icon` + `App\Traits\HasPropertyIcons` — annotate a model relation method with `#[Icon('name')]`, or (for database attributes) the model class with `#[Icon('name', for: 'attribute')]`, and the form/show views pull the Tabler icon for that attribute via `getIconForAttribute()`. Never declare a real property for a database column just to carry an annotation — it shadows Eloquent's attribute handling (breaking soft deletes, restores and timestamps).

A field's `required` marker comes from the model's `Store{Model}Request` (or `Update{Model}Request` when editing), falling back to the column's nullability for attributes the rules don't mention (`get_validation_rules_for_model()` in `helpers.php`).

When adding a field to an entity, update the migration **and** the model's `$fillable` (and `$casts`/`$visible`/`$sortables` as needed) **and** the entity's FormRequest rules; the generic form picks it up automatically. Enum columns are driven by the model's enum cast: `get_enum_class_for_attribute()` spots it, and the form renders a select of the enum's cases (labelled by the enum's own `label()` method where it has one, otherwise by the humanised case name) defaulting to the column's database default.

### Authorization
Role-based via the `UserRole` int enum (`Guest=0, Ensemble=1, Member=2, Moderator=3, Admin=4`) on `users.role`. Policies in `app/Policies/` compare `$user->role->value >= UserRole::X->value`. Controllers call `$this->authorizeResource(Model::class)` in their constructor, and routes attach `->can(...)` / `->middleware('auth')` (see `routes/web.php`). The `Ensemble` role is a shared generic login that can only update attendance polls; `view` on an ensemble also allows non-admins who belong to that ensemble (`$user->ensembles->contains(...)`).

### Soft deletes everywhere
Models use `SoftDeletes`. Index controllers honour a `with_trashed` query param, and each resource has a `restore` route (`PATCH /{resource}/{id}/restore`).

### Sorting
Uses `s-damian/larasort` (`AutoSortable` trait + `$sortables` array on models). Index queries call `->autosort()`; the `<x-larasort-link>` Blade component renders sortable column headers.

### Attendance model
`member_status_totals()` in `helpers.php` computes attending/not-attending/unknown counts for a term date, taking the latest attendance record per member. Behaviour is tuned by custom config keys in `config/app.php`: `readererer_assume_attending`, `readererer_allow_change_to_unknown`, `readererer_repeating_headings` (env-overridable). `AttendanceStatus` enum is `Unknown=0, Attending=1, NotAttending=2`.

### Attendance register
The poll records what members said they *would* do and is append-only; the register (`RegisterEntry`, `AttendanceRegisterController`, `attendance.register.*` routes) records what actually happened on the day and holds one row per member per date per ensemble, updated in place (unique index on `term_date_id + ensemble_id + user_id`). `RegisterStatus` is `Unmarked=0, Present=1, Absent=2, Late=3`; clearing a member back to `Unmarked` with no note deletes their row. `register_status_totals()` in `helpers.php` counts a register, treating members with no row as unmarked. A register only exists for dates that apply to the ensemble — rehearsals apply to everyone, a concert only to the ensemble playing it (`TermDate::appliesToEnsemble()`).

### Uploaded images
`User` and `Ensemble` use the `HasImage` trait: `image` holds either an external URL (seeders) or a path on the `public` disk, so views read `$model->image_url`. Edit forms post as `multipart/form-data`, render `<x-forms.image-upload>` and pass the file and "remove" checkbox to `applyImageInput()`. Saving deletes the file an image replaced, because the servers run no scheduler to sweep orphans.

### Seating plans & PDFs
`SeatingPlanController` edits per-ensemble seating (seat_row/seat_column stored on the `user_ensemble` pivot). PDF output uses `barryvdh/laravel-dompdf` (`SeatingPlanPdfController`, `ensembles.seating-plan.download` route).

### Key domain relationships
- `Term` hasMany `TermDate`; a `TermDate` with `concert_ensemble_id = null` is a rehearsal, otherwise it's that ensemble's concert (see `ShowEnsemble` trait). A term's `earliest_date`/`latest_date` are accessors over its dates, not columns, so they cannot be used in queries.
- `User` belongsToMany `Ensemble` through `user_ensemble` (pivot carries `instrument_family_id`, `seat_row`, `seat_column`); belongsTo `SetupGroup`.
- `Setlist` ↔ `Piece` via `SetlistPiece`; `Piece` belongsTo `Composer`, hasMany `Part` (each part tied to an `InstrumentFamily`).

### Frontend
Blade components live in `resources/views/components/` (Tabler-based: `card`, `table`, `avatar`, `setup-group-badge`, form partials under `components/forms/`). JS/CSS entrypoints are wired through `vite.config.js` + `@tabler/core`. Colours use Tabler names; `color_name_to_hex()` in `helpers.php` maps them to hex for inline styling/PDFs.

### Breadcrumbs
`diglactic/laravel-breadcrumbs` renders the trail in `components/page-header.blade.php`, which is only reached when the layout's `show_page_header` is true. Trails are defined per route name in `routes/breadcrumbs.php` — the generic CRUD resources share one definition that registers their `index`/`create`/`show`/`edit` trails, labelling records by the model's `name` attribute. Add a definition there whenever you add a named GET page; `BreadcrumbsTest` fails if one is missing. Markup lives in `resources/views/vendor/breadcrumbs/tabler.blade.php` (selected by `config/breadcrumbs.php`).

## Conventions

- `Model::preventSilentlyDiscardingAttributes()` is enabled in local env (`AppServiceProvider`) — mass-assignment of non-fillable attributes throws, so keep `$fillable` accurate.
- Helper functions in `app/helpers.php` are global (snake_case); Blade leans on them heavily. Check there before writing new view-logic helpers.
- `docs/development-plan.md` is the running TODO / priorities list — consult it for intended direction and known bugs (e.g. seating-plan row bugs, missing edit views).
- Tabler icons are referenced by name through the `<x-icon>` component / `Icon` attribute, not raw SVG.
- Every form starts with `<x-forms.error-summary :fields="..." />`, passing the field names it renders (wildcards allowed, e.g. `term_dates.*.start_datetime`). It lists the validation errors that have no field of their own to appear beside.
- User-facing text is translatable: wrap it in `__()` (or `trans_choice()` for counts) using the English string as the key, and pass interpolated values as named `:placeholders` rather than concatenating. Enum labels are translated in the enum's own `label()` method; auto-entity column labels are translated centrally by `clean_attribute_name()`. Add new strings to `lang/en.json` — see `lang/README.md`.

## Working preferences

- Write commit messages as Conventional Commits (`type(scope): description`); see `.github/pull_request_template.md`. The release version bump is derived from them.
- Give PR titles the same Conventional Commits form: the title becomes the merge commit on `main` (`title (#N)`). When taking over or superseding another PR, retitle it too.

- Check-ins on PRs (`send_later`/scheduled tasks, webhooks (`watch_url`), PR activity subscriptions or other periodic checks) are fine while development is still active, e.g. waiting on pipeline output. Once development is complete and the PR is in a stable state, remove all of them.
- Do not add code comments unless the behaviour is genuinely obscure; prefer clear naming, and think carefully before adding any comment.
- Write PR descriptions in neutral, factual language: state what changed and why, without hype or self-praise.
