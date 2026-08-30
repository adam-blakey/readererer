# Translations

Every user-facing string in the app goes through Laravel's translator, so the UI
can be shipped in another language without touching Blade or PHP.

## How strings are marked up

- **Views and PHP** wrap literal text in `__()`, using the English text itself as
  the key: `{{ __('Save register') }}`.
- **Interpolated values** are passed as named placeholders rather than
  concatenated, so a translator can move them around the sentence:
  `__('Remove :member from :ensemble?', ['member' => ..., 'ensemble' => ...])`.
- **Counted strings** use `trans_choice()` with a `singular|plural` key:
  `trans_choice(':count member|:count members', $count)`.
- **Enum labels** live on the enum itself (`RegisterStatus::label()`,
  `UserRole::label()`, …) and are translated there, so every view that renders a
  status picks up the translation for free.
- **Auto-entity column labels** are generated from the column name by
  `clean_attribute_name()` in `app/helpers.php` ("setup_group" → "Setup group"),
  which passes the result through `__()`. They therefore appear in the catalogue
  as their generated English form even though no `__('Setup group')` call is
  written anywhere.

## The catalogue

`lang/en.json` is the full list of translatable strings, mapping each English
source string to itself. English needs no translation — the file exists as the
template a translator copies when adding a locale, and as a way to spot strings
that were never extracted.

Anything Laravel itself provides (validation messages, password-reset statuses,
pagination labels — the `foo.bar` dotted keys) is served by the framework's own
message files and is deliberately not listed here. Publish those with
`php artisan lang:publish` if you want to override them.

## Adding a locale

1. Copy `lang/en.json` to `lang/<locale>.json` (for example `lang/cy.json`).
2. Translate the *values*, leaving the keys and any `:placeholder` tokens alone.
   Keep the `|` separator in `singular|plural` values.
3. Set `APP_LOCALE=<locale>` in `.env` (`APP_FALLBACK_LOCALE` stays `en`, so any
   string you have not translated yet falls back to English).

## Keeping it up to date

When you add user-facing text, wrap it in `__()` and add the English string to
`lang/en.json`. Strings missing from the catalogue still render correctly — the
translator falls back to the key — so the file is documentation for translators
rather than something the app depends on.
