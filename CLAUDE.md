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

All environments (dev, qa and the production sites prod, nsw-beta and blackwood-beta) are on Krystal and deployed by GitHub Actions: `.github/workflows/deploy.yml` is a reusable workflow (build, test, deploy to dev) called by `pull-request.yml` for pull requests and by `main.yml` for pushes to `main`, which adds the qa, release, production and docs jobs, so pull requests show no skipped release jobs. Dev and qa deploy through the composite action `.github/actions/deploy-ssh`, the production sites through `.github/actions/deploy-cpanel` and cPanel Git™ Version Control: pull requests deploy to dev; pushes to `main` deploy to dev, then qa, and in parallel (after the `release` environment's wait timer, in `release_tag`, which pushes the release tag) `prod` in `deploy_prod`, then nsw-beta and blackwood-beta together in the `deploy_beta` matrix once `prod` succeeds, then `release` (which also needs qa) publishes the GitHub release. After them, the `docs` job rsyncs the static site in `site/` to `readererer.blakey.family`; the production domains are `readererer-prod.blakey.family` (prod), `attendance-beta.nsw.org.uk` (nsw-beta) and blackwood-beta's `APP_DOMAIN`. For dev and qa the workflow builds `vendor` and `public/build` on the runner, rsyncs them over SSH, resets the server's checkout to the commit and migrates (dev is `migrate:fresh --seed`). The production sites have no SSH: each matrix job calls the cPanel API to pull `main` into the account's clone, which is the site's domain folder itself, and run the tasks in `.cpanel.yml` (which derives the site from the clone's path, so one file serves every site), then polls until the deployment finishes. Those tasks build `vendor` and the assets in `~/readererer-build/<domain>`, then put the app in maintenance mode, rsync them into the domain folder, migrate, re-cache and bring it back up. The host disables `proc_open`, so Composer runs with `--no-scripts` and `package:discover` is its own task. The host also caps virtual memory (address space, not RAM), which a default Vite build exceeds many times over: V8 reserves up to 10 GB per WebAssembly memory and the allocators reserve arenas per thread. So the tasks run Vite through `node --disable-wasm-trap-handler --wasm-max-mem-pages=4096` (`NODE_OPTIONS` rejects that V8 flag) with the allocator and thread-count variables exported beforehand, which builds under a 2 GB cap (the host's is 4 GB). Both actions stop before `artisan down` if the site's `.env` has no valid `APP_KEY` (read with phpdotenv, checked with `Encrypter::supported()`), because `optimize` would cache the bad key and every request would fail in the Encrypter. `artisan down` is skipped while the site's folder has no `vendor`, as on a site's first deploy; on the production sites `vendor` and `bootstrap` are rsynced in just before it, because the pull has already put the new code there and booting it with the old packages fails. After every app deploy, both actions run `.github/actions/check-site`, which fails the job unless `https://<domain>/health` and `/login` answer HTTP 200 (no redirects followed, with retries, sending the `CF_ACCESS_CLIENT_ID`/`CF_ACCESS_CLIENT_SECRET` service token for sites behind Cloudflare Access); the dashboard itself needs a signed-in user. `release_tag` pushes the tag before deploying so the server's `git describe` can write it to `VERSION`. Setup and required repo settings are in `.github/DEPLOYMENTS.md`. Do not invoke deploys yourself.

## Architecture

### Convention-driven generic CRUD ("auto-entities")
Most entities (Composer, Piece, Setlist, Term, etc.) are rendered by shared views in `resources/views/auto-entities/` (`index`, `show`, `form`) rather than per-entity Blade. The forms are built dynamically by reflecting over the model:

- `app/helpers.php` (globally autoloaded via composer `files`) — `get_create_fields()` introspects a model's `getFillable()`, DB column types (`Schema::getColumns`), casts, and relationships to produce a field list (label, html input type, required, icon, options) for the generic form. `map_database_type_to_html()` maps DB types to form inputs. `get_route_name_from_model()` / `get_class_name_from_model()` derive route names like `composers.show` from a model instance.
- `App\Attributes\Icon` + `App\Traits\HasPropertyIcons` — annotate a model relation method with `#[Icon('name')]`, or (for database attributes) the model class with `#[Icon('name', for: 'attribute')]`, and the form/show views pull the Tabler icon for that attribute via `getIconForAttribute()`. Never declare a real property for a database column just to carry an annotation — it shadows Eloquent's attribute handling (breaking soft deletes, restores and timestamps).

A field's `required` marker comes from the model's `Store{Model}Request` (or `Update{Model}Request` when editing), falling back to the column's nullability for attributes the rules don't mention (`get_validation_rules_for_model()` in `helpers.php`).

When adding a field to an entity, update the migration **and** the model's `$fillable` (and `$casts`/`$visible`/`$sortables` as needed) **and** the entity's FormRequest rules; the generic form picks it up automatically. Enum columns are driven by the model's enum cast: `get_enum_class_for_attribute()` spots it, and the form renders a select of the enum's cases (labelled by the enum's own `label()` method where it has one, otherwise by the humanised case name) defaulting to the column's database default.

### Authorization
Role-based via the `UserRole` int enum (`Guest=0, Ensemble=1, Member=2, Moderator=3, Admin=4`) on `users.role`. Policies in `app/Policies/` compare `$user->role->value >= UserRole::X->value`. Resource controllers call `$this->authorizeResource(Model::class)` in their constructor, and every other route declares its check on the route with `->can(...)` (chain several when it needs more than one; pass `[Model::class, 'param']` to hand a policy a route model alongside the class), never with `$this->authorize()` in the controller body. `RouteAuthorisationTest` fails when a route has no `can:` middleware or when a route that changes data checks only a `view*` ability; the few public or signed-in-only routes are listed in it. The `Ensemble` role is a shared generic login that can only update attendance polls; `view` on an ensemble also allows non-admins who belong to that ensemble (`$user->ensembles->contains(...)`). Members and above can view and edit their own user record ("My profile" in the user menu); changing a role, setup group or ensemble memberships needs `UserPolicy::manage`, which is Moderator and above.

A guest refused a GET page (by `auth` or by a policy) is redirected to the login page with the route's name and parameters (`login_url()` in `helpers.php`, wired in `bootstrap/app.php`); logging in redirects to that route if it is a named GET route of the app, otherwise to the dashboard.

Users can also log in with Google (`GoogleLoginController`, `google.redirect`/`google.callback` routes, through `laravel/socialite`) once the settings page turns on `app.readererer_google_login` and fills in `services.google.client_id` and `services.google.client_secret` (`google_login_enabled()` in `helpers.php`). The secret is listed in `GeneralSettings::encrypted()`, so the package encrypts it with `APP_KEY`. A verified Google email signs in the user with that email address (matched case-insensitively). An address with no account gets one only when its domain is `app.readererer_google_register_domain`, with the role in `app.readererer_google_register_role`.

Members can also log in with an emailed link (`LoginLinkController`, `login-link.*` routes), and opening it shows a button that logs in so email link scanners cannot use it up. Password reset links and login links are both an `EmailLinkType` (`ResetPassword`, `LoginLink`), sent by `$type->send($email)` as the `EmailLink` notification: the type decides who may get one, the page it links to and the email's wording, and the token comes from the password broker, so every type is single-use, expires and is throttled alike. Shared `Ensemble` logins can neither request nor use either type of link (`EmailLinkType::credentials()` leaves them out, and `NewPasswordController` resets through it too), nor log in with Google; an admin sets their password instead.

A user with `must_change_password` set (new users, from the web form or `user:create`) is held on the change password page (`password.edit`) by the `EnsurePasswordIsChanged` middleware, appended to the whole `web` group because many routes rely only on `->can()`. Changing or resetting the password clears it.

### Soft deletes everywhere
Models use `SoftDeletes`. Index controllers honour a `with_trashed` query param, and each resource has a `restore` route (`PATCH /{resource}/{model}/restore`, bound `->withTrashed()` and checked with `->can('restore', ...)`).

### Sorting
Uses `s-damian/larasort` (`AutoSortable` trait + `$sortables` array on models). Index queries call `->autosort()`; the `<x-larasort-link>` Blade component renders sortable column headers.

### Attendance model
`member_status_totals()` in `helpers.php` computes attending/not-attending/unknown counts for a term date, taking the latest attendance record per member. Behaviour is tuned by custom config keys in `config/app.php`: `readererer_assume_attending`, `readererer_allow_change_to_unknown`, `readererer_repeating_headings`, `readererer_depping` (env-overridable, and editable on the settings page). `AttendanceStatus` enum is `Unknown=0, Attending=1, NotAttending=2, Depping=3`. With depping on, the poll's fourth (lime) state sends a dep, whose name is stored in `attendances.dep_name`; a dep counts as attending, and while depping is off `AttendanceStatus::effective()` shows an earlier dep as attending and the poll ignores dep replies.

### Attendance register
The poll records what members said they *would* do and is append-only; the register (`RegisterEntry`, `AttendanceRegisterController`, `attendance.register.*` routes) records what actually happened on the day and holds one row per member per date per ensemble, updated in place (unique index on `term_date_id + ensemble_id + user_id`). `RegisterStatus` is `Unmarked=0, Present=1, Absent=2, Late=3`; clearing a member back to `Unmarked` with no note deletes their row. `register_status_totals()` in `helpers.php` counts a register, treating members with no row as unmarked. A register only exists for dates that apply to the ensemble — rehearsals apply to everyone, a concert only to the ensemble playing it (`TermDate::appliesToEnsemble()`).

### Uploaded images
`User` and `Ensemble` use the `HasImage` trait: `image` holds either an external URL (seeders) or a path on the `public` disk, so views read `$model->image_url`. Edit forms post as `multipart/form-data`, render `<x-forms.image-upload>` and pass the file and "remove" checkbox to `applyImageInput()`. Saving deletes the file an image replaced, because nothing sweeps orphaned files.

### Logo
Views show the logo through `logo_url()` in `helpers.php`: the `readererer_logo` config key (`READERERER_LOGO`, a URL or a path under `public/`, or a logo uploaded on the settings page), falling back to the bundled Readererer logo.

### Site settings
Admins edit site-wide options on the settings page (`SettingController`, gated by `edit.settings`). They are stored with `spatie/laravel-settings`: `App\Settings\GeneralSettings` (group `general`, one row per property in the `settings` table) has a nullable property for each, and `GeneralSettings::applyToConfig()` copies every non-null one over the matching config key in `AppServiceProvider::boot()`, so code keeps reading `config(...)` and the `.env` values are the defaults (null means "use the default"). To add one, add the property, map it in `GeneralSettings::CONFIG_KEYS`, add a settings migration in `database/settings` that creates it (`$this->migrator->add('general.<name>')`; the package refuses to save a property with no row), and add it to the form, `UpdateSettingsRequest` and the controller. The wording setting (`app.readererer_terminology`) renames the words in `GeneralSettings::TERMS`: `App\Translation\TerminologyLoader` decorates the translation loader and runs `apply_terminology()` over the JSON catalogue before placeholders are filled, so only strings in `lang/en.json` are renamed and interpolated names are left alone.

The featured colour (`app.readererer_primary_color`, a `Color` case) sets `data-bs-theme-primary` on the layout's `<html>`, so Tabler's `tabler-themes.css` recolours `--tblr-primary` (buttons, links, checked inputs). Illustrations are inlined Tabler SVGs (`resources/views/errors/illustrations/`), whose highlights read `--tblr-primary`; a PNG cannot follow the colour.

Three feature switches live there too. `app.readererer_setup_groups` and `app.readererer_van_drivers` hide every trace of those features (pages, columns, form fields, badges, reminders, roster emails); saving a form whose field is hidden leaves the stored value alone, so turning a feature back on restores its data. `app.readererer_single_ensemble` is for a site with one ensemble: `single_ensemble()` in `helpers.php` returns that ensemble, new users created on the web form join it with the instrument family chosen there, and no second ensemble can be created. It can only be switched on while the site has at most one ensemble.

`EmailDispatcher` sends each email through the `App\Contracts\EmailSender` interface, bound in `AppServiceProvider`: `SmtpEmailSender` sends through Laravel's mailer, and `LogEmailSender` writes the rendered email to `storage/logs/emails.log` (the `emails` log channel) instead. The `app.readererer_log_emails` setting picks the logger. To add a provider such as Sparkpost, implement the interface and choose it in that binding. Password reset emails go through Laravel's notifications, not the dispatcher, so they are always sent.

Reminders: `app.readererer_reminder_hours` (0 = off) is how long before a date its reminders go out, and `app.readererer_concert_reminder_hours`, when set, replaces it for concerts (`TermDate::remindersDueAt()`). The `reminders:send` command, scheduled every fifteen minutes in `routes/console.php` (the sites need a cron running `schedule:run`, see `.github/DEPLOYMENTS.md`), sends the attendance list, setup reminder and van-driver reminder for each date inside that window, once each: a mail counts as sent if its `EmailLog` was created after the reminder time. `app.readererer_after_reminders` decides what happens to poll replies after that time: `nothing`, `follow_up` (each changed reply before the date starts emails the attendance list's recipients an `AttendanceChangedMail`) or `lock` (`TermDate::isLockedByReminders()`). `TermDate::isLockedFor()` closes a date to users below Moderator once it has started, or once it is locked by reminders: the poll ignores and disables their replies.

### Feature flags
Feature flags use Laravel Pennant. Every flag is a class in `app/Features/` (found by `Feature::discover()` in `AppServiceProvider`) that extends `App\Support\FeatureFlag` and only gives a `title()` and `description()`. Admins control each flag on the Feature flags page (`FeatureFlagController`, `feature-flags.*` routes, gated by `edit.settings`): turned on or off, limited to roles and ensembles (none chosen means everyone), a start time (time-based roll-out) and a release (`from_version`, compared with `config('app.version')` ignoring any `-N-gSHA+prN` dev suffix, so dev builds count as the last release). The options are one `feature_flags` setting (`app.readererer_feature_flags`, keyed by flag class), read by `FeatureFlag::resolve()` on every check: Pennant uses its `array` store (`config/pennant.php`), so nothing is persisted and changes and start times take effect on the next request. A new flag starts off for everyone. A flag needing an extra rule overrides `resolve()` and combines it with `parent::resolve($user)`. Check flags with `Feature::active(MyFlag::class)`, `@feature(\App\Features\MyFlag::class)` in Blade, or the `EnsureFeaturesAreActive::using(MyFlag::class)` route middleware; tests turn one on with `config(['app.readererer_feature_flags' => [MyFlag::class => ['enabled' => true]]])` followed by `Feature::flushCache()`.

### Pollererer import
Pollererer, the app Readererer replaces, has an admin-only JSON export (format `pollererer-export`, version 1). `App\Services\PollererImporter` maps it onto ensembles, users, memberships, terms, dates, attendance and settings, and the import page (`PollererImportController`, `settings.import`, gated by `edit.settings`) uploads it, previews it and imports it. The site must have no ensembles, terms, dates or attendance (soft-deleted included). The preview runs the whole import inside `Model::withoutEvents()` and a transaction that it rolls back; the upload waits on the `local` disk under `imports/`, referenced from the session, until it is confirmed and then deleted. Images are only downloaded on the real run, after the commit. Pollererer's bcrypt hashes are written straight to `users.password`, because the `hashed` cast rejects a hash whose cost differs from the configured rounds. The preview warns when rehearsal start times shift between summer and winter, a sign that `APP_TIMEZONE` differs from Pollererer's timezone, and confirming then needs a choice between importing the times unchanged and keeping the clock times Pollererer showed (each time is read in the export's timezone and moved to the same clock time in the site's). Relative ensemble image paths are resolved against the logo URL's origin, the only Pollererer address the export carries, and member rows become seat-row letters (1 is A).

### Logging and monitoring
`AddLogContext` (appended to the `web` group) puts a request ID (also sent as `X-Request-Id`), the route name and pattern, the user ID and the IP into Laravel's `Context`, which is added to every log entry; `AppServiceProvider` adds the site's host. It logs the route pattern rather than the URL so tokens in login and reset links stay out of the logs. The `daily` channel writes JSON (`LOG_DAILY_JSON`); the `http` channel (`App\Logging\HttpLogHandler`) buffers a request's entries and posts them in one request at shutdown to a service such as Better Stack or Axiom, and never throws. Setup is in `.github/DEPLOYMENTS.md`.

Laravel Pulse records exceptions, slow requests, queries and outgoing requests, and per-user request counts into the `pulse_*` tables. Its own dashboard route is turned off (`Pulse::ignoreRoutes()`), because its Livewire and inline scripts break the Content Security Policy, and Livewire, which Pulse requires, is left out of package discovery (`extra.laravel.dont-discover` in `composer.json`) so it registers no routes; admins read the data on the Tabler page `settings.monitoring` (`MonitoringController`, gated by `edit.settings`), which recreates Pulse's overview cards and the layout of Tabler's uptime page through `Pulse::aggregate()`/`Pulse::values()`. The scheduled `health:check` command requests the site's own `/health` every five minutes and records a `health_check` entry (key `up` or `down`, value the response time) and, on failure, a `health_check_failure` value, logging an error; the page reads the raw `health_check` entries for its uptime blocks, response-time line and availability table. `settings.monitoring.logs` shows the newest entries of the latest `storage/logs/laravel*.log`, JSON or line format, filtered by minimum level. Tests run with `PULSE_ENABLED=false`, so tests that need Pulse data call `Pulse::startRecording()` and then `Pulse::ingest()`.

### Seating plans & PDFs
`SeatingPlanController` edits per-ensemble seating (seat_row/seat_column stored on the `user_ensemble` pivot). PDF output uses `barryvdh/laravel-dompdf` (`SeatingPlanPdfController`, `ensembles.seating-plan.download` route).

### Key domain relationships
- `Term` hasMany `TermDate`; a `TermDate` with no `concert_ensembles` (belongsToMany through the `concert_ensemble` pivot) is a rehearsal for every ensemble, otherwise it's a concert for just those ensembles (`is_concert`, `appliesToEnsemble()`, `ShowEnsemble` trait). A term's `earliest_date`/`latest_date` are accessors over its dates, not columns, so they cannot be used in queries. A `hidden` term is left out of listings for users below Moderator (`Term::visibleTo()`) and its poll is a 404 for them.
- `User` belongsToMany `Ensemble` through `user_ensemble` (pivot carries `instrument_family_id`, `seat_row`, `seat_column`); belongsTo `SetupGroup`.
- `Setlist` ↔ `Piece` via `SetlistPiece`; `Piece` belongsTo `Composer`, hasMany `Part` (each part tied to an `InstrumentFamily`).

### Frontend
Blade components live in `resources/views/components/` (Tabler-based: `card`, `table`, `avatar`, `setup-group-badge`, form partials under `components/forms/`). JS/CSS entrypoints are wired through `vite.config.js` + `@tabler/core`. Colours use Tabler names; `color_name_to_hex()` in `helpers.php` maps them to hex for inline styling/PDFs.

Before building UI, look for an existing example and reuse it: first a Blade component or a view in this app that already does something similar, then Tabler's own markup. Prefer Tabler components and Bootstrap utility classes over custom CSS, and only use classes that appear in the Tabler or Bootstrap 5 docs. The official Tabler skill is vendored in `.claude/skills/tabler` (refresh it from `skills/tabler/SKILL.md` in the `tabler/tabler` repo, or with `npx skills add tabler/tabler`). It points to the docs written for agents: https://docs.tabler.io/llms.txt indexes every page, appending `.md` to a docs URL gives the page with its example markup (e.g. https://docs.tabler.io/ui/components/card.md), and https://preview.tabler.io shows each layout and component in use. Where the skill and this file differ (icons go through `<x-icon>`, not inline SVG; assets come from Vite, not the CDN), this file wins.

### Breadcrumbs
`diglactic/laravel-breadcrumbs` renders the trail in `components/page-header.blade.php`, which is only reached when the layout's `show_page_header` is true. Trails are defined per route name in `routes/breadcrumbs.php` — the generic CRUD resources share one definition that registers their `index`/`create`/`show`/`edit` trails, labelling records by the model's `name` attribute. Add a definition there whenever you add a named GET page; `BreadcrumbsTest` fails if one is missing. Markup lives in `resources/views/vendor/breadcrumbs/tabler.blade.php` (selected by `config/breadcrumbs.php`).

## Conventions

- `Model::preventSilentlyDiscardingAttributes()` is enabled in local env (`AppServiceProvider`) — mass-assignment of non-fillable attributes throws, so keep `$fillable` accurate.
- Helper functions in `app/helpers.php` are global (snake_case); Blade leans on them heavily. Check there before writing new view-logic helpers.
- Tabler icons are referenced by name through the `<x-icon>` component / `Icon` attribute, not raw SVG.
- `AddSecurityHeaders` (global middleware) sends a Content Security Policy whose `script-src` allows only same-origin files and a per-request nonce, plus the other security headers; `public/.htaccess` covers files Apache serves directly. So no inline event handlers (`onclick=` etc.): use a data attribute and a listener (`data-confirm` and `data-submit-on-change` are handled in `resources/js/form-actions.js`), and give any inline `<script>` `nonce="{{ Vite::cspNonce() }}"`.
- Every form starts with `<x-forms.error-summary :fields="..." />`, passing the field names it renders (wildcards allowed, e.g. `term_dates.*.start_datetime`). It lists the validation errors that have no field of their own to appear beside.
- User-facing text is translatable: wrap it in `__()` (or `trans_choice()` for counts) using the English string as the key, and pass interpolated values as named `:placeholders` rather than concatenating. Enum labels are translated in the enum's own `label()` method; auto-entity column labels are translated centrally by `clean_attribute_name()`. Add new strings to `lang/en.json` — see `lang/README.md`.
- Use British English spelling in all text you write or change: UI strings, `lang/en.json`, docs, comments, commit messages and PR descriptions (e.g. "colour", "organise", "licence", "behaviour"). Leave code identifiers, framework and library names, and CSS properties as they are (`color`, `Color` enum).

## Working preferences

- Put new behaviour that changes what users see or how existing features work behind a feature flag (see "Feature flags" above), so it can be tried with a few roles or ensembles, timed, or tied to a release before it reaches everyone. Once a flag is on for everyone and has settled, remove the class and its checks.
- Write commit messages as Conventional Commits (`type(scope): description`); see `.github/pull_request_template.md`. The release version bump is derived from them.
- Give PR titles the same Conventional Commits form: the title becomes the merge commit on `main` (`title (#N)`). When taking over or superseding another PR, retitle it too.

- Check-ins on PRs (`send_later`/scheduled tasks, webhooks (`watch_url`), PR activity subscriptions or other periodic checks) are fine while development is still active, e.g. waiting on pipeline output. Once development is complete and the PR is in a stable state, remove all of them.
- Do not add code comments unless the behaviour is genuinely obscure; prefer clear naming, and think carefully before adding any comment.
- Write PR descriptions in neutral, factual language: state what changed and why, without hype or self-praise.
- When a PR touches or updates the UI, take screenshots of the affected pages (e.g. with Playwright against `php artisan serve`) and include them in the PR description. Never commit them to the PR's branch: push them to the orphan `pr-screenshots` branch (you may push there) under a folder named after the PR's branch, e.g. `git worktree add --orphan -b pr-screenshots ../shots` the first time, otherwise `git fetch origin pr-screenshots && git worktree add ../shots pr-screenshots`, then commit and push. Link each image by that commit's SHA, `https://raw.githubusercontent.com/adam-blakey/readererer/<sha>/<folder>/<file>.png`, so the links outlive deleted branches.
