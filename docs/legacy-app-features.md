# Legacy App — Features & Functionality

My understanding of the original project (`git@github.com:hellerbenjamin/band.git`), captured for review before we draw up dev plans for the restart. The most complete branch is **`master`** (Inertia + services + the active-band concept + gigs); `feature/vuetify` is an earlier experiment that contributed the richer `bands` schema and the factories/seeders.

> Status legend: ✅ working · 🟡 partial · ⏹️ stub (route/method exists, empty) · ❌ planned but not built

## 1. What the app is

A **band-booking / gig-management** tool. A user can belong to multiple bands; they pick one "active band" and everything they see (members, gigs, venues) is scoped to it. Built as a server-driven SPA.

**Stack (legacy `master`):** Laravel 12, Inertia.js, Vue 3, **Vuetify 3** (Material Design), Ziggy (named routes in JS), MDI icons. Auth is hand-rolled (not Breeze/Jetstream).

> Restart note: we are replacing the frontend with **PrimeVue + Vue 3** and have not committed to Inertia yet — an open question below.

## 2. Core concept: the Active Band

The pivotal idea in the whole app:

- A user's currently-selected band is stored in the **session** (`active_band_id`) by `BandSessionService`, exposed app-wide through an `ActiveBand` **facade**.
- `HandleInertiaRequests` shares `activeBand` (plus `auth.user` and `flash` messages) as global Inertia props, so every page knows the active band.
- `HasActiveBandMiddleware` guards the main app: if the user has **no bands**, redirect to the band-creation wizard; if they have bands but none is active, auto-select the first.
- A **BandSwitcher** dropdown in the app bar lists the user's bands (with their role shown as a chip) and lets them switch (`POST /bands/{id}/set-active`) or create a new band.

This is the backbone we'll want to preserve regardless of frontend.

## 3. Feature areas

### Authentication & onboarding 🟡
- ✅ Register (`/register`): custom `RegisterController` + `RegisterRequest` (name, email, confirmed password). Creates user, fires `Registered`, logs in, redirects to dashboard.
- ✅ Login (`/login`): email/password, "remember me", session regenerate. Notably **blocks the literal password `"default"`** — see invited-members below.
- ✅ Logout.
- ❌ Password reset — `password_reset_tokens` table exists but no controller/flow.
- ❌ Email verification — `Registered` event fired but no verification enforcement.

### Bands 🟡
- ✅ Create band (`/bands/create` → `store`): currently only captures **name** (creation "wizard" is boilerplate). On create, the creator is auto-assigned the **OWNER** role and the band becomes active.
- ✅ List user's bands (`/bands`, also surfaced on dashboard) with pivot role.
- ✅ Set active band (`/bands/{id}/set-active`).
- Backed by `BandService` (create + owner assignment) and `BandSessionService` (active-band state).

### Band members 🟡
- ✅ List members of the active band (`/band-members`) with user details, via `BandUserService::getBandUsers`.
- ✅ Add member (`/band-members/create`, `store`): add **by email**. If no user with that email exists, one is created with a placeholder password `"default"` (hence the login block above — invited users must reset before logging in). Only **Member** and **Admin** are assignable through the UI (not Owner).
- 🟡 Remove member (`DELETE /band-members/{bandUser}`): intended to block removing the **owner**, but see bug #3 — the controller bypasses the protective service method.
- Authorization: adding members requires `BandPolicy::update` (Admin/Owner) via `StoreBandUserRequest::authorize`.

### Gigs 🟡
- ✅ List gigs for active band (`/gigs`): returns JSON for XHR, otherwise the Inertia index page. `GigService::getBandGigs` supports `before` / `after` / `venue_id` filters (filtering not yet wired to UI).
- ✅ Create gig (`/gigs/create`, `store`): form with venue (select from active band's venues), date, optional name/start/end time/notes/fee. Validated by `StoreGigRequest` (e.g. end-time-after-start-time).
- ⏹️ Show / edit / update / destroy: routes exist via `Route::resource`, but controller methods are **empty** (service has `updateGig`/`deleteGig` ready to use).

### Venues ❌ (data + helpers only)
- `venues` table and `Venue` model exist and gigs reference them, but there is **no VenueController and no venue routes** — venues can't be created/managed in-app yet (the gig form just lists whatever venues exist).
- Two services were built in anticipation: `GeographicDataService` (countries/states from JSON resource files) and `USPSService` (address validation via USPS API — has no auth/key, effectively a stub). The nav has a "Venues" item but it's not linked.

### Dashboard & navigation 🟡
- ✅ Dashboard greets the user and shows their bands / active band.
- 🟡 Stats cards (e.g. "Total Songs: 24") are **hardcoded placeholders**.
- Navigation drawer items: Dashboard ✅, Band Members ✅, Gigs ✅, Venues ❌, **Music** ❌ and **Settings** ❌ (placeholders, no routes/features).

## 4. Authorization model

`BandPolicy` keys permissions off the user's role in the band (`Band::getUserRole`):
- **view / update** (e.g. manage members): Admin or Owner
- **delete / restore** band: Owner only
- **viewAny / forceDelete**: denied

## 5. Architecture patterns worth keeping

- **Service layer** per domain (`BandService`, `BandUserService`, `GigService`, `UserService`) keeps controllers thin.
- **Active-band facade + session service** for cross-cutting "current band" state.
- **Inertia shared props** for auth user, flash messages, active band.
- **Form Requests** for validation + authorization.
- **Custom exceptions** (`NoActiveBandsException`, `ActionNotAllowedException`) for control flow.

## 6. Known bugs & gaps (do not recycle as-is)

1. **Role casing mismatch** (already noted in `data-model.md`): pure enum returned uppercase names while the DB/filters used lowercase — fixed in the new string-backed `BandUserRoleEnum`.
2. **`BandPolicy` is internally inconsistent**: `view`/`delete` compare against enum *cases* while `update` compares against `->value()` *strings*. With the old enum these never reliably matched. Needs rewriting against the new backed enum.
3. **Owner-deletion protection is bypassed**: `BandUserController::destroy` calls `$bandUser->delete()` directly instead of `BandUserService::deleteBandUser()` (which throws `ActionNotAllowedException` for owners). The controller even catches that exception, but it can never be thrown. Route-model binding also skips any band-scoping/authorization check on the deleted member.
4. **Gig CRUD incomplete** — show/edit/update/destroy are empty stubs.
5. **No venue management** — needed before gigs are truly usable (currently no way to add a venue in-app).
6. **No password reset / email verification** despite the supporting table and event.
7. **`USPSService`** has no API credentials — non-functional as written.
8. **Layout duplication**: `AppLayout.vue` exists but several pages (Dashboard, Gigs/Index) re-implement the whole app bar + drawer inline instead of using it; nav links are inconsistent (`route('BandMember.Index')` vs `route('band-members.index')`). A persistent layout is the better pattern.
9. **`AppServiceProvider`** binds an `'ActiveBand'` singleton to the facade class itself — redundant/incorrect; the facade resolves `BandSessionService` directly.

## 7. Open questions for the dev plan

- **Inertia or not?** Legacy was Inertia+Vuetify. The restart is PrimeVue — do we keep Inertia (and swap Vuetify→PrimeVue), or go API + client-side routing, or Blade+islands? This is the biggest architectural fork.
- **Scope of v1:** which of the gaps above are in-scope for the first cut (venue CRUD, gig edit/delete, password reset, real dashboard stats)?
- **Bands as rich profiles:** the schema has genre + many social links — is band profile editing a v1 feature or later?
- **Invitations:** keep the "create user with `default` password on invite" pattern, or move to a proper invitation/email flow?
- **Geographic/USPS features:** keep address validation as a goal, or drop it for v1?

---

See [data-model.md](data-model.md) for the schema and [band-booking-color-palette.md](band-booking-color-palette.md) for design tokens.
