# Rough-edges triage

Closes the recurring "quantify/triage rough edges" item in `docs/todos.md`: form
error handling, the "resizing text disappearing" issue, automated tests, and a
possible Laravel Boost setup.

This document **measures** each of the four strands and turns them into concrete,
sized follow-up work. It deliberately contains no fixes — the point of the
exercise was to find out how big each pile actually is before committing to it.

Measured on PHP 8.4.19, Laravel 13, Pest 4, against this branch merged with
`afb3b69`. `main` moved twice while this was being written, and each time the
measurements were re-run rather than left to rot: the colour picker (#87), the
hover-details work (#88), the generic-form field polish (#89) and the breadcrumb
and dirty-check work (#92, #93) have all landed since the first pass, and
between them they closed most of strand 1. Every number below is reproducible;
see [How the numbers were measured](#how-the-numbers-were-measured).

## Summary

| # | Strand | Verdict | Size |
| --- | --- | --- | --- |
| 1 | [Form error handling](#1-form-error-handling) | **Largely fixed on `main` while this was being written.** What is left is the schema/rules mismatch, the missing error summary, and `image` fields | ~3 hours |
| 2 | ["Resizing text disappearing"](#2-resizing-text-disappearing) | **Reproduced and located.** Three components hide their text below 1200px; one renders an empty link | ~1–2 hours |
| 3 | [Automated tests](#3-automated-tests) | **Healthier than it looks** (413 passing) but nothing runs them — no CI job executes the suite or Pint | ~half a day |
| 4 | [Laravel Boost](#4-laravel-boost) | **Compatible, low cost, genuinely useful here** — recommend adopting | ~1 hour |

Suggested order: 2 (cheapest, most visible), 3 (protects everything after it),
then 1 and 4.

Strand 1 shrinking twice under its own follow-up work is the strongest argument
in this document for doing 3 first. Each of those fixes was correct, but the
first one shipped with a hole in it — the relationship select — that no test
caught, and it was still open a day later. CI would have.

---

## 1. Form error handling

The per-field error display works: a failed rule adds `is-invalid` to the input
and renders the message in an `invalid-feedback` div
(`resources/views/components/forms/field.blade.php:151-153`), and the redirect
lands back on the form with the submitted values intact.

### Fixed on `main` during this triage

Three of the five findings originally recorded here are now closed, all by
`45ed22a` "Polish the generic form field partial" and the old-input fix before
it. Recorded because the story matters for strand 3, not as outstanding work:

| Was | Now |
| --- | --- |
| Every generic *create* form discarded what the user typed, because `old()` sat behind an `isset($data['value'])` guard and a fresh model's attributes are all `null` | Fixed — `$value = old($name, $data['value'] ?? null)` (`field.blade.php:16`) |
| That fix missed the `class` branch, so the relationship select alone still lost the user's choice | Fixed — the branch now normalises models, collections and raw old ids to a collection of id strings and drives `@selected` off it (`field.blade.php:30-40`, `:80`). Re-measured: pick "Group B", fail validation, and Group B comes back selected |
| `date` rendered no input at all; `boolean` and `email` fell through to a plain text input | Fixed — `date`, `datetime`, `boolean`, `email` and `password` all have their own cases (`field.blade.php:87-107`) |
| Two `// TODO`s (icon alignment on error, "style nice") and a `\|\|`-should-be-`&&` error check | Fixed — the message now sits outside the icon wrapper, and the check is `filled($error_message)` |

### F2 — The form's `required` markers are derived from the schema, not the rules

`get_create_fields()` sets `required` from the column's nullability
(`app/helpers.php:168`), while the actual rules live in the `FormRequest`. They
still disagree in both directions:

| Field | Form says | Request says | Result |
| --- | --- | --- | --- |
| `users.email` | optional (column is nullable) | `required` (`StoreUserRequest`) | The browser lets the submission through, then the server bounces it |
| `setupgroups.week` | `required` (column is `NOT NULL`) | `nullable` (`StoreSetupGroupRequest`) | The browser blocks a submission the server would have accepted |

2 of the 4 live generic forms disagree with their own validation rules.

### F3 — `image` still renders as a free-text input

With `date`, `datetime`, `boolean`, `email` and `password` now handled, `image`
is the last type `map_database_type_to_html()` emits that `field.blade.php` has
no case for, so it falls through to `@default` and asks for a URL as free text.
Live on `users`, though `UserController@create` `unset()`s it, so it only shows
on the edit form.

### F4 — No form-level error summary

Errors are only ever rendered next to their own field; nothing in
`resources/views/` calls `$errors->any()`. An error on a key that isn't in the
rendered field list is therefore invisible — the user is bounced back to an
apparently fine form with no explanation.

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
the `<a>`, so below 1200px the ensemble page renders a **clickable link with no
visible content at all**.

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

The hover-text work in #88 does not address this — a `title` tooltip doesn't
help text that was never rendered, and on a touch device there is no hover.

---

## 3. Automated tests

Better than the item's tone suggests. **413 tests / 993 assertions, all
passing** in ~8s. The gaps are about what *isn't* covered and, more importantly,
about nothing running them.

### T1 — No CI job runs the tests or Pint (highest severity)

Five workflows exist (`codeql`, `php-security`, `deploy-dev`, `deploy-qa`,
`deploy-production`) and **none of them runs `php artisan test` or
`./vendor/bin/pint --test`**. A PR that breaks every test still gets a green
check and a built dev image. This is the single highest-value item in this whole
document: one workflow file.

### T2 — The suite needs `npm run build` to pass, and doesn't say so

`tests/Unit/IconComponentTest.php` asserts against `public/build/icons/*.svg`.
Measured by moving `public/build` aside: **9 of its 10 tests fail** until
`npm run build` has been run. Any CI job added for T1 must build assets first
(or the test should skip when the build directory is absent).

### T3 / T4 — Coverage gaps that are really unbuilt features

71 of 100 routes are exercised. Of the 29 that aren't, 21 are the
composer/piece/setlist CRUD — and 42 of 126 public controller methods are
`{ // }` stubs, 14 of them on live routes. An empty action returns a blank
`200`, so a status-code assertion would pass against a page that does nothing.

This is Phase 2 feature work (`docs/todos.md`), not test debt, and is not
proposed as a follow-up issue below.

### T5 — 3 of 9 model factories can't create a model

| Factory | Result of `Model::factory()->create()` |
| --- | --- |
| `SetupGroupFactory` | Throws — `definition()` is `//`, `name`/`color` are `NOT NULL` |
| `AttendanceFactory` | Throws — `Attempt to read property "id" on null` |
| `EnsembleAdmin` | No `factory()` method at all (model lacks `HasFactory`) |

Consequently tests hand-roll `SetupGroup::create([...])` in at least 6 places.
`ComposerFactory`, `PieceFactory`, `SetlistFactory` and `PartFactory` are
present but near-empty (0–2 attributes).

### T6 — Pint has never been run: 133 of 234 files fail

`./vendor/bin/pint --test` fails on **133 files** (57%). Top fixers:
`ordered_imports`, `fully_qualified_strict_types`, `single_blank_line_at_eof`,
`braces_position`, `class_definition`, `no_unused_imports`.

Running `./vendor/bin/pint` fixes all of them and the suite still passes —
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
Blade file. Boost's schema and route tools give an agent exactly that.

Cost: `composer require --dev laravel/boost` then `php artisan boost:install`.
Dev-only, so it never ships. The one thing to decide is whether the generated
guidelines file duplicates or replaces parts of `CLAUDE.md`.

---

## Follow-up issues

Each finding below is filed as its own issue:

| Issue | Covers | Est. |
| --- | --- | --- |
| #97 — Member names disappear below 1200px | R: `user-entry`, `poll-entry` (+ decide on `name-and-role`) | 1–2 h |
| #98 — Run the test suite and Pint in CI | T1, T2 | 1–2 h |
| #99 — Format the codebase with Pint | T6 | 30 min |
| #100 — `required` markers come from the schema, not the rules | F2 | 2 h |
| #101 — Errors with no visible field are invisible | F4 | 1–2 h |
| #102 — `image` renders as a free-text box | F3 | 1–2 h |
| #103 — Fill in the model factories | T5 | 2 h |
| #104 — `ext-pdo` constraint breaks install below PHP 8.4.24 | T7 | 5 min |
| #105 — Set up Laravel Boost | Strand 4 | 1 h |

Ordering that falls out of the above: #104 (5 minutes, and it blocks CI), then
#99, then #98 — after which everything else is protected by a suite that
actually runs. #97 is independent and the most visible to members, so it can go
first if a quick win is wanted.

T3/T4 are not listed: they are Phase 2 feature work already tracked in
`docs/todos.md`, not test debt.

---

## How the numbers were measured

```bash
composer install --ignore-platform-req=ext-pdo   # see T7
npm install && npm run build                     # see T2
php artisan key:generate

# Strand 3
php artisan test                                 # 413 passed
./vendor/bin/pint --test                         # 133 files
find app config database routes tests bootstrap -name '*.php' | wc -l   # 234
php artisan route:list --json                    # 100 app routes
```

Route coverage (T3) was measured by temporarily listening for
`Illuminate\Routing\Events\RouteMatched` in `tests/TestCase::setUp()`, logging
each matched route name to a file, running the full suite and diffing the unique
names against `route:list`. Empty controller actions (T4) were counted by
matching `public function …() { // }` across `app/Http/Controllers`, then
intersecting with `route:list`. T2 was measured by moving `public/build` aside
and re-running `tests/Unit/IconComponentTest.php`. The form behaviour in F1/F2
was measured with a throwaway feature test that posted invalid data to
`setupgroups.store` / `users.store` and inspected the HTML of the redisplayed
form. None of that instrumentation is committed.
