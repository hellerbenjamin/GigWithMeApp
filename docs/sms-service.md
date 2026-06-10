# SMS service

GigWithMe sends low-volume transactional texts — gig confirmations and reminders
to band members and venue contacts. Phone numbers live on `users.phone_number`
and venue `contact_phone`.

## Decision: Vonage

We use **Vonage** via Laravel's first-party notification channel
[`laravel/vonage-notification-channel`](https://github.com/laravel/vonage-notification-channel).

History: we originally wired up Twilio
(`laravel-notification-channels/twilio`), but switched to Vonage once the Vonage
account was verified for sending. Vonage is the channel Laravel maintains itself,
so it's one fewer community dependency, and pricing is comparable
(~$0.0072/msg + carrier fees). Amazon SNS was considered as the cheapest option
but has weaker delivery tracking and more setup.

## A2P 10DLC (applies regardless of provider)

US business SMS requires **A2P 10DLC registration** — register the brand and
campaign with the carriers before sending. This is a few days of approval plus
a small monthly fee and is not provider-specific. Budget time for it before
launch.

## Wiring (done)

1. ✅ `composer require laravel/vonage-notification-channel` (v3.3).
2. ✅ Credentials live in `config/services.php` under the `vonage` key, fed from
   `.env`: `VONAGE_KEY`, `VONAGE_SECRET`, `VONAGE_SMS_FROM` (the sender ID or
   number), and optional `VONAGE_DEBUG_TO` (used only by `gigwithme:test-sms` to
   route every test message to one number in local/staging).
3. ✅ `User::routeNotificationForVonage()` returns `phone_number`.
4. ✅ The gig notifications (`GigConfirmed`, `GigPollOpened`,
   `GigPollNeedsAttention`) implement `ShouldQueue` and pick channels via the
   `RoutesGigNotification` trait, which adds `'vonage'` when the member has a
   phone number. Each has a `toVonage()` method returning a `VonageMessage`. The
   `dev` script's `queue:listen` delivers them async.

### Sending

```php
$user->notify(new GigConfirmed($gig));
```

For a venue contact (no User row), send on-demand to `venues.contact_phone`:

```php
Notification::route('vonage', $venue->contact_phone)
    ->notify(new GigConfirmed($gig));
```

### Smoke test

```sh
ddev artisan gigwithme:test-sms                 # sends to VONAGE_DEBUG_TO
ddev artisan gigwithme:test-sms +19071234567    # or an explicit number
```

### Notes

- Phone numbers must be **E.164** (`+15551234567`). `users.phone_number` /
  `venues.contact_phone` should be stored that way or normalized before send.
- For emoji / non-GSM characters, call `->unicode()` on the `VonageMessage`
  (the test notification does this).
