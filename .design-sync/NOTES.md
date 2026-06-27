# GigWithMe Design Sync — Notes

## Setup

- **Shape**: tokens-only (no React component library — GigWithMe uses Vue 3 + PrimeVue, which
  the design-sync converter cannot bundle). Only brand tokens, colors, typography, and radii
  are synced.
- **Converter**: NOT used. The `ds-bundle/` directory is hand-maintained; there is no
  `dist/` entry to bundle.
- **Upload path**: incremental (project was empty on first sync).

## Re-sync process

This is a hand-maintained tokens-only sync. To re-sync after palette or typography changes:

1. Edit `ds-bundle/tokens/tokens.css` to match `resources/css/app.css` and `resources/js/theme.js`.
2. Edit `ds-bundle/README.md` if the descriptions or usage guidance need updating.
3. Re-run `/design-sync` — it will find the existing `projectId` in `.design-sync/config.json`
   and upload the changed files via the atomic path.

The `.ds-sync/` toolchain and `package-build.mjs` are NOT needed for this repo.

## Known issues / gotchas

- The palette in `docs/band-booking-color-palette.md` is OUTDATED (it documents the old
  Amp Violet palette). The source of truth is `resources/css/app.css` for Tailwind tokens
  and `resources/js/theme.js` for the PrimeVue primary scale.
- The current palette is in "PREVIEW" mode (earthy/Pacific Cyan), as noted by a comment
  block in both `app.css` and `theme.js`. If the preview is reverted to Amp Violet, update
  tokens.css accordingly.
- Fonts (Instrument Sans, Bricolage Grotesque) are loaded from Google Fonts at runtime via
  an `@import url(...)` in `styles.css`. No `@font-face` declarations are shipped.

## Re-sync risks

- **Palette drift**: if `app.css` color values change without updating `ds-bundle/tokens/tokens.css`,
  the design agent will build with stale colors.
- **Inline Google Fonts URL**: the `styles.css` `@import url(...)` requires network access
  to load fonts in the design agent's sandbox. If Google Fonts changes its URL format,
  update `styles.css`.
- **No anchor**: `_ds_sync.json` was not generated (tokens-only, hand-maintained). Future
  re-syncs will always re-upload all files — expected and fine.
