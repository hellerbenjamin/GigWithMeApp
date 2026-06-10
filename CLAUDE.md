# GigWithMe

A band-booking / gig-management app. Laravel + Vue 3 + PrimeVue 4 (Aura preset)
+ Tailwind CSS 4, served via ddev.

The name is an invitation — *gig with me* — so the product voice is warm and
first-person: GigWithMe helps a band rally its people and keep its dates on the
books. The marketing site lives at **gigwith.me**; the app itself is
**gigwithme.app** (SMS/booking links point at the app domain). The wordmark
renders as one word, "GigWithMe".

Note: "roadie" still exists in the domain as a band-member *role* (crew) — that
is not the app name and should stay.

## Conventions

- **Never commit without explicit permission.** Make and stage changes, but do
  not run `git commit` until I ask for it in that moment — a prior "commit" is
  not standing approval for later changes. When work is ready, say so and wait.
  (Commits still go straight to `master`, no feature branches.)
- **Plans and context go in `docs/`.** When we write up implementation plans,
  design notes, or background/context for the codebase, add them as Markdown
  files in the `docs/` folder so they live alongside the code.
- **No em dashes in copywriting.** In user-facing copy (legal pages, marketing,
  UI text, notification wording), avoid the em dash (—). Rephrase, or use a
  comma, colon, or parentheses instead.

## Theme

- Brand palette is documented in `docs/band-booking-color-palette.md`.
- PrimeVue theme is a custom Aura-based preset in `resources/js/theme.js`
  (`BandPreset`), wired up in `resources/js/app.js`. Primary = Amp Violet;
  light-mode surfaces use the warm neutral ramp (Canvas → Ink).
- Brand colors are also exposed as Tailwind tokens in `resources/css/app.css`
  under `@theme`, so utilities like `bg-encore-coral` / `text-soundcheck-teal`
  are available.
