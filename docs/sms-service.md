# SMS service

Roadie sends low-volume transactional texts — gig confirmations and reminders
to band members and venue contacts. Phone numbers live on `users.phone_number`
and venue `contact_phone`.

## Decision: Twilio

We use **Twilio** via the community Laravel notification channel
[`laravel-notification-channels/twilio`](https://github.com/laravel-notification-channels/twilio).

Why Twilio over the alternatives:

- **Twilio** — mature, well-documented channel; errors are easy to diagnose
  and the community is large. ~$0.0079/msg + carrier fees. **(chosen)**
- **Vonage** — built into Laravel core as the default SMS channel (no extra
  package), ~$0.0072/msg. Runner-up; only edge was "one less dependency."
- **Amazon SNS** — cheapest (~$0.00645/msg) and natural on AWS, but weaker
  delivery tracking and more setup.

## A2P 10DLC (applies regardless of provider)

US business SMS requires **A2P 10DLC registration** — register the brand and
campaign with the carriers before sending. This is a few days of approval plus
a small monthly fee and is not Twilio-specific. Budget time for it before
launch.

## Wiring (done)

1. ✅ `composer require laravel-notification-channels/twilio` (v4.2).
2. ✅ Credentials come from `.env` via the package's own
   `twilio-notification-channel` config namespace — **not** `config/services.php`.
   Vars added to `.env.example`: `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN`,
   `TWILIO_FROM`, and optional `TWILIO_DEBUG_TO` (routes all SMS to one number
   in local/staging).
3. ✅ `User::routeNotificationForTwilio()` returns `phone_number`.
4. ✅ `App\Notifications\GigConfirmed` implements `ShouldQueue`, sends via
   `TwilioChannel` with a `toTwilio()` method. The `dev` script's
   `queue:listen` delivers it async.

### Sending

```php
$user->notify(new GigConfirmed($gig));
```

For a venue contact (no User row), send on-demand to `venues.contact_phone`:

```php
Notification::route('twilio', $venue->contact_phone)
    ->notify(new GigConfirmed($gig));
```

### Notes

- Phone numbers must be **E.164** (`+15551234567`). `users.phone_number` /
  `venues.contact_phone` should be stored that way or normalized before send.
- To publish the package config for editing:
  `php artisan vendor:publish --provider="NotificationChannels\Twilio\TwilioProvider"`.
