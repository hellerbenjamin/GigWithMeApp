# Gig Booking Flow

Design + implementation plan for how a gig moves from *created* to *confirmed*,
covering the three flows discussed. Lives here per the `docs/` convention in
CLAUDE.md.

## Decisions (settled)

1. **Booking mode is chosen per gig, with a band-level default.** The admin
   picks a mode when creating a gig; the band setting just pre-selects it. One
   gig can auto-confirm while another polls.
2. **Flow 3 (advance availability) is a later phase.** We design the schema so
   it's cheap to bolt on, but build only flows 1 & 2 now.
3. **Members respond via a magic link as the day-one baseline; RCS is a later
   enhancement.** The response-capture layer is transport-agnostic so web link,
   SMS reply, or RCS button taps all funnel into one internal action.

## The three flows

| Flow | Mode value | Behavior |
|------|-----------|----------|
| 1. Auto-accept | `auto` | Gig is created already `confirmed`. Members get a heads-up (existing `GigConfirmed` SMS). No response needed. |
| 2. Poll | `poll` | Gig is created `pending`. Each member is asked available / not. **All available → auto-confirm.** **All replied, some unavailable → admins notified to decide.** Still waiting → nothing. |
| 3. Advance availability | `availability` *(deferred)* | Members keep a personal availability calendar. At gig creation the admin is warned of conflicts, and poll responses can be pre-filled from known availability. |

Flows 1 & 2 are *confirmation strategies*. Flow 3 is an *orthogonal layer* that
feeds the others — which is why deferring it costs nothing if the schema below
leaves room for it.

## Data model

### `gigs` (add columns)
- `booking_mode` — string, default `'auto'`. Cast to `GigBookingModeEnum`.
  - The existing `status` (`GigStatusEnum`: pending / confirmed / cancelled)
    stays the source of truth for *where the gig is*. `booking_mode` only
    decides *how it gets confirmed*.

### `bands` (add column)
- `default_booking_mode` — string, default `'auto'`. Pre-selects the create-gig
  form's mode. Add to `Band` `#[Fillable]` and surface in band settings.

### `gig_member_responses` (new table)
The per-member poll record. One row per (gig, member), seeded when a `poll` gig
is created.

| Column | Type | Notes |
|--------|------|-------|
| `id` | id | |
| `gig_id` | FK → gigs, cascade | |
| `user_id` | FK → users, cascade | the band member |
| `status` | string | `pending` / `available` / `unavailable`. Default `pending`. |
| `responded_at` | timestamp, nullable | set when they answer |
| `channel` | string, nullable | `web` / `sms` / `rcs` … — which transport recorded it (analytics) |
| `note` | text, nullable | optional "only if it starts after 9" |
| `token` | string, unique | unguessable; powers the magic link **and** becomes the RCS button payload later |
| timestamps | | |

- **Unique** `(gig_id, user_id)`.
- Seeding upfront (vs. lazily) makes "has everyone replied?" and "who's
  outstanding?" trivial, and gives flow 3 a place to pre-fill.

### `member_availability` (flow 3, **not built now** — sketch only)
`user_id`, `band_id`, `date`, `status` (available / unavailable), `note`. At gig
creation, look up rows for the date and warn the admin; optionally pre-fill the
poll responses. Designing `gig_member_responses.status` with a `pending` state
is what makes that pre-fill a no-op change later.

## New enum

`App\Enums\GigBookingModeEnum: string`
- `Auto = 'auto'`, `Poll = 'poll'` now.
- Reserve `Availability = 'availability'` (commented / deferred).
- Mirror the existing `GigStatusEnum` shape: `label()`, `values()`, and a short
  description for the form helper text.

## Service layer

Keep controllers thin (per the architecture decision). Split booking lifecycle
out of CRUD:

- **`GigService`** — unchanged responsibilities (queries + plain CRUD).
- **`GigBookingService`** (new) — owns the booking/poll lifecycle:
  - `applyMode(Gig $gig)` — called right after creation. `auto` → confirm +
    notify; `poll` → seed `gig_member_responses` for current members + send
    `GigPollOpened` to each.
  - `recordResponse(GigMemberResponse $r, bool $available, string $channel, ?string $note)`
    — **the single transport-agnostic entry point.** Sets status/`responded_at`/
    `channel`, then calls `evaluatePoll`.
  - `evaluatePoll(Gig $gig)`:
    - all `available` → `confirm()` (status = confirmed, fire `GigConfirmed` to
      all members).
    - all responded & ≥1 `unavailable` → `GigPollNeedsAttention` to admins/owner;
      gig stays `pending`.
    - otherwise → no-op (still collecting).
  - `confirm(Gig $gig)` — used by both auto mode and poll success, and by an
    admin's manual "confirm anyway".

`GigController::store` stays thin: create via `GigService`, then hand to
`GigBookingService::applyMode`.

## Magic-link RSVP (baseline transport)

- Route `GET /rsvp/{token}` → guest-accessible page (no login) showing the gig
  summary + **Available** / **Can't make it** buttons + optional note field.
- `POST /rsvp/{token}` → `RsvpController` → `GigBookingService::recordResponse(…, channel: 'web')`.
- The `token` is the authorization (unguessable, scoped to one gig+member,
  revocable by clearing it). Rate-limit the routes. Members may change their
  answer until the gig is confirmed/cancelled.
- This same token is what an RCS quick-reply button or an SMS-reply webhook will
  carry later — no rework to the core when those land.

## Notifications

- `GigPollOpened` (new) → each member, SMS with the magic link:
  *"{band}: can you play {where} on {when}? {link}"*
- `GigConfirmed` (exists, currently never dispatched) → wire it up. Fires on
  auto-confirm (flow 1) **and** poll success.
- `GigPollNeedsAttention` (new) → admins/owner:
  *"Everyone replied for {where} {when} — not all available. Review: {link}"*
- RCS variants are a **later** custom channel (see below), not in this build.

## Frontend (Inertia + PrimeVue)

- **Create gig form** — add a Booking Mode `Select` (Auto-confirm / Poll
  members), pre-selected from the band default, with helper text from the enum.
  Add `booking_mode` to `StoreGigRequest` validation (`in:` enum values).
- **Gig list / detail** — show poll progress for `poll` gigs (e.g. `4/5 ✓` with
  per-member chips: available / unavailable / waiting). For admins, when a poll
  needs attention, show **Confirm anyway** and **Re-poll** actions.
- **Band settings** — a Default Booking Mode select.
- **RSVP page** — a lightweight guest Inertia page for `/rsvp/{token}`.

## Edge cases & rules

- **Who is polled:** all band members regardless of role (they're the
  performers). The creator, if a member, is auto-marked `available`.
- **Membership changes mid-poll:** member added → add a `pending` response;
  member removed → drop their response and re-evaluate (a removal might complete
  the poll).
- **Editing a poll gig's date:** offer **Re-poll** — reset responses to
  `pending` and resend `GigPollOpened` (stale "yes" to the old date shouldn't
  silently carry over).
- **Admin manual confirm** (the needs-attention path): just `confirm()`.
- **Single-member / creator-only band:** poll trivially auto-confirms.
- **Idempotent / changed answers:** responding again updates the row until the
  gig leaves `pending`.

## Authorization

- Create / confirm / re-poll a gig: admin or owner (`BandPolicy`).
- Respond to a poll: the member the `token` belongs to (token *is* the auth for
  the magic link; session user for the in-app path).
- All gig routes stay scoped to the active band (the existing `abort_unless($gig->band_id === ActiveBand::id(), 404)` pattern).

## Build phases

> Status: Phase 1 ✅ done. Phase 2 🟡 **core done** (responses, seeding, magic-link
> RSVP, evaluate → confirm / needs-attention, both SMS notifications, tests) —
> see "Next steps" for the Phase 2 work still outstanding. Phases 3–4 not started.

1. **Auto-confirm (flow 1) — smallest.** ✅ `GigBookingModeEnum`; `booking_mode` +
   `default_booking_mode` migrations; create-form select + band setting; wire
   `GigConfirmed` to dispatch on `auto` create. `GigBookingService::applyMode` +
   `confirm`.
2. **Poll (flow 2).** 🟡 Done: `gig_member_responses` migration + model; seed on
   `poll` create; `GigPollOpened`; `RsvpController` + magic-link routes/page;
   `recordResponse` + `evaluatePoll`; `GigPollNeedsAttention`. **Not yet done —
   see Next steps:** admin review UI, re-poll, membership-change handling.
3. **RCS enhancement (later).** Custom Twilio channel via the Content API
   (quick-reply actions) + inbound webhook mapping the button payload (the
   `token`) into `recordResponse(channel: 'rcs')`. Gated on RBM agent
   verification; SMS magic link remains the fallback.
4. **Advance availability (flow 3, later).** `member_availability` table +
   calendar UI; conflict warning at gig creation; pre-fill poll responses.

## Next steps (outstanding)

Captured from the build so far — the gaps I flagged, in rough priority order:

1. **Admin poll UI (finishes Phase 2).** The needs-attention path currently only
   fires an SMS; there's no in-app surface. Add to the gig list/detail:
   - poll progress for `poll` gigs (e.g. `4/5 in`, per-member chips by
     `GigResponseStatusEnum::severity()`);
   - for owners/admins on a closed-but-unconfirmed poll: **Confirm anyway**
     (→ `GigBookingService::confirm`) and **Re-poll** actions.
2. **Re-poll + membership changes (finishes Phase 2).**
   - Editing a poll gig's **date** offers Re-poll: reset responses to `pending`,
     clear `poll_closed_at`, resend `GigPollOpened` (decided: always offer).
   - Member **added** mid-poll → add a `pending` response; member **removed** →
     drop their response and re-`evaluatePoll` (a removal can complete the poll).
3. **Phase 3 — RCS** quick-reply channel (see RCS notes below).
4. **Phase 4 — advance availability** (flow 3): `member_availability` table +
   calendar, conflict warning at creation, pre-fill poll responses.

## RCS notes (for phase 3)

- RCS gives tappable suggested-reply buttons whose taps return a **structured
  payload** — no free-text parsing. Ideal for the poll.
- Prerequisites: a registered RBM / RCS sender + brand verification through
  Google's RCS Business Messaging (Twilio is the aggregator) — a days-to-weeks
  approval, not a config flag.
- Coverage isn't universal (device + carrier dependent; business RCS to iPhone
  still limited as of early 2026), so **SMS fallback is mandatory** — RCS can
  never be the only channel.
- `laravel-notification-channels/twilio` covers SMS/MMS/calls, **not** RCS rich
  content, so this is a custom channel hitting Twilio's Content API.
- Re-verify Twilio's current RCS capabilities before starting phase 3.
