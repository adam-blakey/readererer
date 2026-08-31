# Rough-edges triage

Closes the recurring "quantify/triage rough edges" item in `docs/todos.md`: form
error handling, the "resizing text disappearing" issue, automated tests, and a
possible Laravel Boost setup.

This document **measures** each of the four strands and turns them into concrete,
sized follow-up work. It deliberately contains no fixes — the point of the
exercise was to find out how big each pile actually is before committing to it.

Measured on PHP 8.4.19, Laravel 13, Pest 4, against this branch merged with
`95028fc` — i.e. including the colour picker (#87), the hover-details work (#88)
and the generic-form old-input fix that landed while this triage was being
written. Every number below is reproducible; see [How the numbers were
measured](#how-the-numbers-were-measured).

## Summary

| # | Strand | Verdict | Size |
| --- | --- | --- | --- |
| 1 | [Form error handling](#1-form-error-handling) | **Smaller than it was.** The big one (create forms discarding input) landed on `main` mid-triage; what remains is the relationship select, which still loses its choice, plus three unrendered field types | ~3–4 hours |
| 2 | ["Resizing text disappearing"](#2-resizing-text-disappearing) | **Reproduced and located.** Three components hide their text below 1200px; one renders an empty link | ~1–2 hours |
| 3 | [Automated tests](#3-automated-tests) | **Healthier than it looks** (389 passing) but nothing runs them — no CI job executes the suite or Pint | ~half a day |
| 4 | [Laravel Boost](#4-laravel-boost) | **Compatible, low cost, genuinely useful here** — recommend adopting | ~1 hour |

Suggested order: 2 (cheapest, most visible), 3 (protects everything after it),
then 1 and 4. Strand 1 shrank while this was being written — see F1 — which is
itself an argument for doing 3 first: the fix that landed was not covered by a
test, and the half of it that was missed (F1's relationship select) would have
been caught by one.

---

## 1. Form error handling

The per-field error display itself works: a failed rule adds `is-invalid` to the
input and renders the message in an `invalid-feedback` div
(`resources/views/components/forms/field.blade.php:87-89`), and the redirect
lands back on the form. The problems are around it.

### F1 — Fixed on `main` mid-triage, except for relationship selects

The original finding here was that *every* generic create form discarded what the
user typed, because `field.blade.php` guarded `old()` behind
`isset($data['value'])` and a fresh model's attributes are all `null`. **That was
fixed on `main` while this triage was being written** — the line now reads:

```php
$value = old($name, $data['value'] ?? null);
```

Re-measured against the merged head, text, number and colour fields all come back
populated after a failed validation (`week` returns as `3`, the colour swatch
returns as `red`).

**What the fix missed:** the `class` branch — the select rendered for a
`BelongsTo`/`BelongsToMany` relationship — never reads `$value`. It still compares
against `$data['value']`, the model's own value (`field.blade.php:32`):

```php
$selected = ($data['value'] instanceof Collection) ? $data['value']->contains($option->id) : $data['value'] == $option;
```

So the relationship select still loses the user's choice. Measured on
`users.create` — submit a valid `setup_group` of "Group B" with an invalid email:

```
first_name: value="Ada"          <- kept, per the fix above
setup_group: option 1 "Group A"
             option 2 "Group B"  <- neither is selected; the user picked Group B
```

**Affects:** `users.setup_group` and `setupgroups.van_drivers`. The fix is to use
`$value` in that branch as the other branches now do, being careful that
`old()` returns ids as strings and, for a `select_multiple`, an array.

### F2 — The form's `required` markers are derived from the schema, not the rules

`get_create_fields()` sets `required` from the column's nullability
(`app/helpers.php:168`), while the actual rules live in the `FormRequest`. They
disagree in both directions:

| Field | Form says | Request says | Result |
| --- | --- | --- | --- |
| `users.email` | optional (column is nullable) | `required` (`StoreUserRequest`) | The browser lets the submission through, then the server bounces it |
| `setupgroups.week` | `required` (column is `NOT NULL`) | `nullable` (`StoreSetupGroupRequest`) | The browser blocks a submission the server would have accepted |

2 of the 4 live generic forms disagree with their own validation rules.

### F3 — Three field types render nothing usable

`map_database_type_to_html()` emits `text`, `textarea`, `number`, `boolean`,
`date`, `enum`, `color`, `class`, `image` and `email`. `field.blade.php` handles
`class`, `textarea`, `number`, `color`, `enum`, a dead `checkbox` case (never
emitted), and a `default` text input. So:

| Emitted type | Rendered as | Note |
| --- | --- | --- |
| `date` | **nothing at all** — `@case('date')` is an empty `@break` (`field.blade.php:46-47`) | Latent: no entity currently on the generic form has a date column, but `TermDate` (`start_datetime`, `end_datetime`, both `NOT NULL`) would hit it immediately |
| `boolean` | plain text input | Latent — no live entity has one |
| `email` | plain text input, no `type="email"` | Live on `users` |
| `image` | plain text input (free-text URL) | Live on `users`, though the controller `unset()`s it on create |

`checkbox` is dead code: nothing ever produces that type.

### F4 — No form-level error summary

Errors are only ever rendered next to their own field. An error on a key that
isn't in the rendered field list — e.g. `password`, which `UserController@create`
`unset()`s (`app/Http/Controllers/UserController.php:52`) — is invisible: the
user is bounced back to an apparently fine form with no explanation.

### F5 — Cosmetic, pre-existing `// TODO`s

- `field.blade.php:19` — icon alignment when an error is present.
- `field.blade.php:28` — "style nice" on the `class` select.
- `field.blade.php:6` — `$error_message != null || $error_message != ''` should
  be `&&`. It happens to give the right answer for every value `$errors->first()`
  can return, so it is a readability fix, not a bug.

---

## 2. "Resizing text disappearing"

**Reproduced and located.** Three components wrap their text in
`d-none d-xl-block`, so below Bootstrap's `xl` breakpoint (1200px) the text is
`display: none` and only the avatar survives:

| File | What disappears |
| --- | --- |
| `resources/views/components/user-entry.blade.php:11` | The member's **name** and their instrument/role line |
| `resources/views/components/name-and-role.blade.php:3` | The signed-in user's name and role in the navbar |
| `resources/views/components/poll-entry.blade.php:4` | The term name *and* its date range — the whole body of the link |

`poll-entry` is the worst of the three: the `d-none` div is the *only* child of
the `<a>` (lines 3–7), so below 1200px the ensemble page renders a
**clickable link with no visible content at all**.

`user-entry` is the most widely felt — it is used on five screens, including the
two that matter most on a small device:

- `components/attendances/register.blade.php` — taking the register on the day
- `components/attendances/poll.blade.php` — the attendance poll
- `ensembles/seating-plan.blade.php` (×2), `ensembles/edit.blade.php`,
  `ensembles/show.blade.php`

On a phone or tablet — exactly where you would take a register — you are marking
members present against avatar initials only.

This looks like Tabler's navbar-avatar idiom (where hiding the name next to an
avatar in a cramped navbar is deliberate) copy-pasted into full-page list
contexts where there is plenty of room. The fix is per-component, not global:
`name-and-role` in the navbar can keep the behaviour; `user-entry` and
`poll-entry` should show their text at all widths and truncate if needed.

`nav-menu.blade.php:136`'s `d-md-none d-lg-inline-block` on a nav-link *icon* is
the genuine Tabler idiom and is fine.

---

## 3. Automated tests

Better than the item's tone suggests. **389 tests / 929 assertions across 37 test
files, all passing** in ~10s. The gaps are about what *isn't* covered and, more
importantly, about nothing running them.

### T1 — No CI job runs the tests or Pint (highest severity)

Five workflows exist (`codeql`, `php-security`, `deploy-dev`, `deploy-qa`,
`deploy-production`) and **none of them runs `php artisan test` or
`./vendor/bin/pint --test`**. A PR that breaks every test still gets a green
check and a built dev image. This is the single highest-value item in this whole
document: one workflow file.

### T2 — The suite needs `npm run build` to pass, and doesn't say so

`tests/Unit/IconComponentTest.php` asserts against `public/build/icons/*.svg`. On
a clean checkout, `php artisan test` gives **9 failures** until `npm run build`
has been run. Any CI job added for T1 must build assets first (or the test should
skip when the build directory is absent).

### T3 — 29 of 100 routes are never exercised

71/100 routes are hit by the suite. Of the 29 that aren't, 21 are the
composer/piece/setlist CRUD — which matters because of T4.

### T4 — 14 routed actions are empty stubs, and nothing notices

42 of 126 public controller methods are `{ // }` stubs. Most are unrouted, but
**14 live routes resolve to an empty action**:

```
POST  composers            GET composers/create   GET  composers/{composer}
PATCH composers/{composer} GET composers/{composer}/edit
POST  pieces               GET pieces/create      PATCH pieces/{piece}
GET   pieces/{piece}/edit
POST  setlists             GET setlists/create    GET  setlists/{setlist}
PATCH setlists/{setlist}   GET setlists/{setlist}/edit
```

An empty action returns a blank `200`, so a status-code assertion would pass
against a page that does nothing. `EndpointAccessTest` covers *access control* on
index routes and does not touch these; the Composer/Piece/Setlist policies
currently deny `viewAny` to everyone, which is what keeps this out of sight.

This is Phase 2 work (`docs/todos.md`) — flagged here so the "missing tests"
number isn't mistaken for a testing problem when it is really unbuilt features.

### T5 — 3 of 9 model factories can't create a model

| Factory | Result of `Model::factory()->create()` |
| --- | --- |
| `SetupGroupFactory` | Throws — `definition()` is `//`, `name`/`color` are `NOT NULL` |
| `AttendanceFactory` | Throws — `Attempt to read property "id" on null` |
| `EnsembleAdmin` | No `factory()` method at all (model lacks `HasFactory`) |

Consequently tests hand-roll `SetupGroup::create([...])` in at least 6 places.
`ComposerFactory`, `PieceFactory`, `SetlistFactory` and `PartFactory` are
present but near-empty (0–2 attributes).

### T6 — Pint has never been run: 131 of 229 files fail

`./vendor/bin/pint --test` fails on **131 of 229 files** (57%). Top fixers:
`ordered_imports` (87 files), `fully_qualified_strict_types` (60),
`single_blank_line_at_eof` (40), `braces_position` (32), `class_definition` (21),
`no_unused_imports` (20).

Running `./vendor/bin/pint` fixes all 131 and **the suite still passes** —
verified. It is a safe one-shot, but it should land as its own commit, before
the T1 CI job starts enforcing it, so it doesn't pollute a feature diff.

### T7 — `composer install` fails on PHP < 8.4.24

`composer.json` requires `"ext-pdo": "^8.4.24"`. The PDO extension reports the
PHP version, so this pins the project to PHP ≥ 8.4.24 by a side effect of an
extension constraint — `composer install` dies on PHP 8.4.19 and needs
`--ignore-platform-req=ext-pdo`. Almost certainly meant to be `"ext-pdo": "*"`.

---

## 4. Laravel Boost

**Recommendation: adopt it.**

`laravel/boost` gives an AI coding agent Laravel-specific context — an MCP server
exposing the app's routes, database schema, config, logs and Tinker, plus
framework-version-matched guidelines.

Compatibility is confirmed rather than assumed. `laravel/boost` **v2.7.0**
requires `php ^8.2` and `illuminate/* ^11.45.3|^12.41.1|^13.0`, so it fits this
project (PHP 8.4, Laravel 13). A `composer require --dev laravel/boost --dry-run`
resolves cleanly, adding 4 packages (`laravel/boost`, `laravel/mcp`,
`laravel/roster`, `composer/semver`) with **no** changes to existing versions and
no security advisories.

It is a good fit here specifically because this codebase is convention-driven in
a way a general-purpose agent guesses wrong: the "auto-entities" layer means a
field's behaviour comes from the *schema plus the model's casts*, not from any
Blade file. Boost's schema and route tools give an agent exactly that, and F1–F3
above are the kind of thing it would surface directly.

Cost: `composer require --dev laravel/boost` then `php artisan boost:install`.
Dev-only, so it never ships. The one thing to decide is whether the generated
guidelines file duplicates or replaces parts of `CLAUDE.md`.

---

## Proposed follow-up issues

| Proposed issue | Covers | Est. |
| --- | --- | --- |
| Show member names below 1200px | R: `user-entry`, `poll-entry` (+ decide on `name-and-role`) | 1–2 h |
| Run the test suite and Pint in CI | T1, T2 | 1–2 h |
| Format the codebase with Pint | T6 | 30 min |
| Keep the relationship select's choice on a failed validation | F1 | 1–2 h |
| Show a form-level summary for errors with no visible field | F4 | 1–2 h |
| Align generic-form `required` with the FormRequest rules | F2 | 2 h |
| Render date/boolean/email/image fields in the generic form | F3, F5 | 3 h |
| Fill in the model factories | T5 | 2 h |
| Fix the `ext-pdo` constraint | T7 | 5 min |
| Set up Laravel Boost | Strand 4 | 1 h |

T3/T4 are not listed: they are Phase 2 feature work already tracked in
`docs/todos.md`, not test debt.

---

## How the numbers were measured

```bash
composer install --ignore-platform-req=ext-pdo   # see T7
npm install && npm run build                     # see T2
php artisan key:generate

# Strand 3
php artisan test                                 # 389 passed
./vendor/bin/pint --test                         # 131 files
find app config database routes tests bootstrap -name '*.php' | wc -l   # 229
php artisan route:list --json                    # 100 app routes
```

Route coverage (T3) was measured by temporarily listening for
`Illuminate\Routing\Events\RouteMatched` in `tests/TestCase::setUp()`, logging
each matched route name to a file, running the full suite and diffing the unique
names against `route:list`. Empty controller actions (T4) were counted by
matching `public function …() { // }` across `app/Http/Controllers`, then
intersecting with `route:list`. The form behaviour in F1/F2 was measured with a
throwaway feature test that posted invalid data to `setupgroups.store` /
`users.store` and inspected the HTML of the redisplayed form. None of that
instrumentation is committed.
