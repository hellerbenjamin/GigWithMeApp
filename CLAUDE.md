# Band Booking App

Laravel + Vue 3 + PrimeVue 4 (Aura preset) + Tailwind CSS 4, served via ddev.

## Conventions

- **Plans and context go in `docs/`.** When we write up implementation plans,
  design notes, or background/context for the codebase, add them as Markdown
  files in the `docs/` folder so they live alongside the code.

## Theme

- Brand palette is documented in `docs/band-booking-color-palette.md`.
- PrimeVue theme is a custom Aura-based preset in `resources/js/theme.js`
  (`BandPreset`), wired up in `resources/js/app.js`. Primary = Amp Violet;
  light-mode surfaces use the warm neutral ramp (Canvas → Ink).
- Brand colors are also exposed as Tailwind tokens in `resources/css/app.css`
  under `@theme`, so utilities like `bg-encore-coral` / `text-soundcheck-teal`
  are available.
