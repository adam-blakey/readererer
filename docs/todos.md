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
> same dimension Phase 1 uses for seating. The generic "auto-entities" CRUD layer
> affects entities in both phases.

---

## Phase 1 — Attendance diary

### Planning TODOs (from `docs/development-plan.md`)

**Attendance**
- Show who you're playing with for upcoming concerts and rehearsals.
- Register history: a per-member view of how often they have actually turned up
  (the register itself is now built; only the reporting on top of it is missing).

**Dashboard**
- Show the next date against each setup group.

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

- ~~**Enum support in the generic form**~~ — done: a column with an enum cast is
  rendered as a select of the enum's cases, labelled by the enum's `label()` method
  where it has one and defaulting to the column's database default.
- ~~**Generic form field polish**~~ — done: the validation message now sits outside
  the icon wrapper (so the icon stays centred on the control), selects are padded
  clear of the icon, and the field types the form can produce but never rendered
  (booleans, dates, datetimes) render proper controls.
- ~~**Auto-entity show view button alignment**~~ — done: the card header's actions are
  now a `btn-list`, so the Edit link and the Archive/Unarchive form sit side by side
  with the standard gap instead of the form block dropping onto its own line.
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
