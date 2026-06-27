# GigWithMe Mobile

React Native app for band members, built with Expo SDK 56 + expo-router.

This is the member-facing app: view gigs, RSVP, see set lists. Band management
lives in the Laravel web app only.

## API

All data comes from `gigwithme.app/api/v1` (local: `band.ddev.site/api/v1`).
See `../docs/api/openapi.yaml` for the full contract. Auth is Sanctum bearer
tokens — obtained via magic-link exchange or password login, stored on device,
sent as `Authorization: Bearer <token>` on every request.

The deep-link scheme is `gigwithme://` — magic-link emails open the app at
`gigwithme://auth?token=<token>`, which the router handles to exchange the
token for a Sanctum bearer.

## Running locally

```
npm start          # starts Metro + shows QR code
```

Scan the QR with Expo Go (iOS/Android) for instant reload. No simulator or
device build required during early development.

When native modules outside the Expo SDK are added (push notifications, etc.),
switch to a dev build: `eas build --profile development --platform android`
installs a custom dev client APK with the same fast-refresh workflow.

## Shipping test builds

```
eas build --profile preview --platform all
```

Produces an internal-distribution APK (Android) and ad-hoc IPA (iOS). EAS
posts a shareable install URL — no store submission needed for testing.

iOS requires: Apple Developer account, and testers' UDIDs registered in the
provisioning profile (or use TestFlight once the app exists in App Store Connect).

Production builds: `eas build --profile production --platform all` then
`eas submit --platform all`.

## Key files

- `app.json` — Expo config (bundle IDs: `app.gigwithme.member`)
- `eas.json` — EAS build profiles (development / preview / production)
- `app/` — expo-router file-based routes
