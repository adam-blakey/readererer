# Readererer outstanding TODOs

This document catalogues the outstanding work in the codebase and maps each item to
one of the two delivery phases.

## The two phases

- **Phase 1 — Attendance diary.** Everything around running an ensemble's calendar:
  members/users, ensembles, terms and rehearsal/concert dates, attendance polls and
  the register, seating plans, setup-group / van-driver rosters, notifications and
  emails, the dashboard, and the auth/role plumbing that gates all of it.
- **Phase 2 — Digital sheet music.** The library side: composers, pieces, the parts
  that make up each piece (one per instrument family), and the setlists that group
  pieces and attach them to term dates.

How entities split across the phases:

| Phase 1 (attendance diary) | Phase 2 (digital sheet music) | Shared / foundational |
| --- | --- | --- |
| `User`, `Ensemble`, `EnsembleAdmin`, `UserEnsemble` | `Composer` | `InstrumentFamily` |
| `Term`, `TermDate`, `Attendance` | `Piece`, `Part` | generic CRUD ("auto-entities") |
| `SetupGroup`, van drivers | `Setlist`, `SetlistPiece` | |
| `EmailLog`, `EmailRecipient`, notifications | | |

> Note: `InstrumentFamily`, `Part` and the seating plan straddle both phases — parts
> belong to the Phase 2 sheet-music library but are keyed by instrument family, the
> same dimension Phase 1 uses for seating. The generic "auto-entities" CRUD layer and
> its enum gap (see Cross-cutting) affect entities in both phases.

---

## Phase 1 — Attendance diary

### Planning TODOs (from `docs/development-plan.md`)

**Members / users**
- Edit users within ensembles (recurring item — appears under several focus dates).
- `users.edit` should allow adding the user to ensembles (multi-dropdown) and changing
  their setup group.
- Users edit page gives a 500 error.
- Index view should show the name of the user role (mostly done — role enum text now
  pulls through; verify).

**Ensembles**
- Ensemble edit page should allow adding/removing users.
- Members list view (table) for an ensemble.
- Check ensembles actually have users before making polls visible.

**Terms / term dates**
- Add setup groups to term dates.
- Term dates editor has overlapping duplicate and removal buttons.
- Term dates view (table), with options to send emails.

**Attendance**
- Attendance register (in progress, marked `[~]`).
- Show who you're playing with for upcoming concerts and rehearsals.

**Seating plan**
- Seating plan should be looped over rather than living under terms.
- Better styling: strikethroughs and colours.
- Split the seating plan up by instrument.
- Bug: too many / too few new rows created in the seating-plan editor.

**Setup groups**
- Show the hex colour in the index view.

**Notifications / emails**
- Proper notifications system, with an overview and types: setup-group reminder,
  van-driver reminder, and "groups/drivers changed" alerts.
- Emails (general).
- Useful logging.

**Dashboard**
- Date formatting (e.g. "next rehearsal" on the dashboard).
- Show the next date against each setup group.

**Login**
- Tab ordering should skip the "forgotten password" link.

---

## Phase 2 — Digital sheet music

### Planning TODOs (from `docs/development-plan.md`)

**Composer**
- Composer edit view.
- Composer index throws a 500 error when viewing archived (trashed) records.

> The pieces / parts / setlists library is otherwise rendered through the generic
> auto-entities CRUD; no Phase-2-specific code TODOs are currently left in source.
> The Phase 2 sheet-music work (uploading/serving actual PDFs/scores per part) is not
> yet represented by TODO markers — it is implied by the domain model (`Part` ↔
> `InstrumentFamily`) but not started.

---

## Cross-cutting / infrastructure (affects both phases)

These touch the shared generic-CRUD layer or general UX and so apply regardless of phase.

- **Enum support in the generic form** — `app/helpers.php:77`, `app/helpers.php:118`,
  `app/helpers.php:150`: enum columns are not mapped to form inputs; `get_create_fields()`
  needs to populate options/default for enums and `map_database_type_to_html()` needs to
  handle them. This is the known enum gap called out in CLAUDE.md.
- **Generic form field polish** — `resources/views/components/forms/field.blade.php:13`
  (icon alignment when an error is present), `:20` ("style nice"), `:39` (something
  "apparently isn't working correctly").
- **Auto-entity show view button alignment** — `resources/views/auto-entities/show.blade.php:22`.
- **Show real hex in tables** — `resources/views/components/table.blade.php:86`: currently
  prints a literal `#rrggbb` placeholder instead of the actual colour.
- **Proper colour picker** — recurring item in the plan (used by setup groups, etc.).
- **Canonical breadcrumb navigation** — recurring item; plan suggests
  `diglactic/laravel-breadcrumbs`.
- **Translation strings** — i18n not yet extracted.
- **Grey out the Save button when no changes have been made.**
- **Quantify/triage rough edges** — form error handling; "resizing text disappearing"
  issue; automated tests; possible Laravel Boost setup.

---

*Generated from a sweep of `// TODO` / `FIXME` markers across `app/`, `resources/`,
`routes/`, and the running TODO list in `docs/development-plan.md`. Completed (`[X]`)
plan items are omitted.*
