# SMS consent & the invite flow

## Why this exists

GigWithMe sends gig notifications by SMS via Vonage (see
[`sms-service.md`](./sms-service.md)). US A2P 10DLC registration requires that
every recipient has given **explicit, prior consent** to be texted — and that
consent comes from the person who owns the phone, not from someone adding them.

Today there is a compliance gap: an admin types a band member's `phone_number`
on the member form and SMS starts flowing automatically (see
`RoutesGigNotification::via()`). The recipient never opted in. Carriers reject
exactly this pattern. This doc designs the flow that closes the gap.

## Principles

- **Consent is individual.** Only the phone's owner can opt in. An admin cannot
  consent on a member's behalf, and a checkbox the admin ticks does not count.
- **No SMS before consent.** Adding a member creates a *pending invite*, not a
  textable contact. The Vonage channel stays off until the member opts in.
- **Auditable.** We record who consented, when, to what wording, and how.
- **Revocable.** STOP opts a number out immediately; HELP returns help text.

## The flow

1. **Admin adds a member** — name + email. (Phone is no longer collected here;
   the member supplies and confirms their own number at step 3.) This creates
   the member in an `invited` state. No SMS is sent.
2. **Invitation email** — the member receives an email with a unique,
   signed/expiring link to an acceptance page on `gigwithme.app`. Email is the
   chosen delivery channel precisely so the first SMS only ever reaches someone
   who has already said yes.
3. **Acceptance page** — the member confirms their own mobile number and ticks
   an **unticked-by-default** opt-in box. Submitting the form is the consent
   event.
4. **Consent recorded** — we persist the timestamp, the confirmed number, the
   exact opt-in language shown, and the source. The member moves to `active`.
   Only now does `via()` permit the `vonage` channel.
5. **Opt-out / help** — an inbound webhook handles `STOP` (disable SMS to that
   number, set opt-out timestamp) and `HELP` (reply with help text). STOP flips
   the consent flag off without deleting the member.

## Opt-in language (exact wording)

The acceptance-page checkbox must show this, and we store it verbatim with the
consent record:

> Text me about my band's gigs. I agree to receive recurring SMS from GigWithMe
> at this number (gig polls, confirmations, and reminders). Msg & data rates may
> apply. Reply STOP to unsubscribe, HELP for help.

## Data model changes

Add to `users` (or a dedicated `sms_consents` table if we want full history):

- `phone_number` — confirmed by the member (already exists; meaning changes:
  it is no longer admin-entered).
- `sms_consent_at` (nullable timestamp) — when consent was given. Null = no
  consent = no SMS.
- `sms_consent_text` (nullable string/text) — the exact wording agreed to.
- `sms_opted_out_at` (nullable timestamp) — set on STOP; clears consent gating.

`via()` should send SMS only when `sms_consent_at` is set **and**
`sms_opted_out_at` is null.

## Member invite states

`invited` → (accepts + opts in) → `active`. A member can be `active` for the
app but still SMS-off if they declined the opt-in box (they'd then get email /
web push only, which the existing channel-selection already supports).

## Still needed for carrier / Vonage submission

- A **privacy policy** page (e.g. `/privacy`) covering SMS: that we don't sell
  numbers, the message types, frequency, and STOP/HELP. Link it from the
  acceptance page.
- The acceptance page should be **publicly reachable** (behind the signed link)
  so reviewers can see the opt-in screen / screenshot it.
- `STOP` / `HELP` inbound handling wired to the Vonage number's webhook.

## Open questions

- One consent flag per user, or per-number history in a separate table? History
  is safer for audits but heavier. Start with the user columns; migrate later if
  needed.
- Does the admin-add step need the member's email to be unique across bands (a
  member could be in two bands)? Probably reuse the existing user if the email
  matches, and re-use prior consent rather than re-prompting.
