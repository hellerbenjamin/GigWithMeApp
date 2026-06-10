<?php

namespace App\Notifications\Concerns;

use NotificationChannels\WebPush\WebPushChannel;

/**
 * Picks the delivery channels for a gig notification:
 *
 * - web push when the member has an active subscription — it supplants SMS, so
 *   engaged members get free, instant, actionable alerts and the Vonage bill
 *   tapers off;
 * - otherwise SMS via Vonage when we have a phone number *and* the member has
 *   opted in to texts themselves (carrier A2P rules; see
 *   docs/sms-consent-invite-flow.md);
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
        } elseif (filled($notifiable->phone_number ?? null) && $this->mayTextNotifiable($notifiable)) {
            $channels[] = 'vonage';
        }

        if (filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Whether the member has personally consented to SMS. Anything that isn't a
     * User (e.g. an on-demand Notification::route('vonage', ...)) has no consent
     * record to check, so we defer to the caller's explicit routing.
     */
    private function mayTextNotifiable(object $notifiable): bool
    {
        return ! method_exists($notifiable, 'hasSmsConsent') || $notifiable->hasSmsConsent();
    }
}
