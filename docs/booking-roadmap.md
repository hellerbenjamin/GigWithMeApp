# Booking Roadmap

Admin-facing feature that tracks venue outreach across booking seasons. The goal is to keep the band on top of which venues to contact, when to follow up, and how negotiations progress, with a history that grows richer every year.

## Data Model

### `booking_seasons`
A named campaign window per band. One season = one kanban board.
- `band_id`, `name` (user-defined, e.g. "Summer 2026"), `starts_on` / `ends_on` (optional), `notes`

### `venue_outreach`
One row per venue per season. The pipeline card.
- `booking_season_id`, `venue_id`
- `status` enum: `targeting` → `contacted` → `in_discussion` → `booked` | `declined` | `no_response`
- `priority` enum: `high` | `medium` | `low`
- `follow_up_on` (date) — when to reach back out; overdue ones surface in dashboard
- `notes` (text)

### `outreach_contacts`
Individual touch points logged against an outreach record.
- `venue_outreach_id`, `occurred_on` (date), `method` enum: `email` | `phone` | `in_person` | `other`
- `summary` (text — what was said), `response` (text, nullable — what they said)

## Screens

- `/booking/seasons` — list all seasons, create new, see stats per season
- `/booking/seasons/{season}` — kanban board (6 status columns), add venues, carry-forward dialog
- `/booking/outreach/{outreach}` — venue detail: status/priority/follow-up/notes + contact log

## Carry-Forward

When starting a new season, open the carry-forward dialog from any roadmap,
pick a prior season, and select which venues to import. Selected venues land in
the new season as `targeting` with no contact history (fresh start per season).
Already-present venues are skipped.

## Dashboard Widget

"Booking follow-ups" section shows venues where `follow_up_on <= today` and
status is not yet terminal (booked/declined/no_response). Links directly to
outreach detail. Visual only for now; SMS reminders deferred.

## Routes

```
GET  /booking/seasons                              seasons index
POST /booking/seasons                              create season
GET  /booking/seasons/{season}                     roadmap (kanban)
PUT  /booking/seasons/{season}                     update season
DELETE /booking/seasons/{season}                   destroy season
POST /booking/seasons/{season}/outreach            add venue to season
POST /booking/seasons/{season}/carry-forward       carry forward from another season
GET  /booking/outreach/{outreach}                  outreach detail
PUT  /booking/outreach/{outreach}                  update (status/priority/follow_up/notes)
DELETE /booking/outreach/{outreach}                remove from pipeline
POST /booking/outreach/{outreach}/contacts         add contact entry
PUT  /booking/contacts/{contact}                   edit contact entry
DELETE /booking/contacts/{contact}                 delete contact entry
```
