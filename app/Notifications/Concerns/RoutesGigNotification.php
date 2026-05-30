<?php

namespace App\Notifications\Concerns;

use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\WebPush\WebPushChannel;

/**
 * Picks the delivery channels for a gig notification:
 *
 * - web push when the member has an active subscription — it supplants SMS, so
 *   engaged members get free, instant, actionable alerts and the Twilio bill
 *   tapers off;
 * - otherwise SMS via Twilio when we have a phone number;
 * - plus email whenever we have an address (alongside push or SMS).
 *
 * A member with none of the above is simply skipped. Centralizing the choice
 * here keeps every gig notification consistent without repeating the logic.
 */
trait RoutesGigNotification
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if (method_exists($notifiable, 'hasPushSubscription') && $notifiable->hasPushSubscription()) {
            $channels[] = WebPushChannel::class;
        } elseif (filled($notifiable->phone_number ?? null)) {
            $channels[] = TwilioChannel::class;
        }

        if (filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        return $channels;
    }
}
