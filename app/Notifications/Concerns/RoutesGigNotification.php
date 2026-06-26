<?php

namespace App\Notifications\Concerns;

use NotificationChannels\WebPush\WebPushChannel;

/**
 * Picks the delivery channels for a gig notification:
 *
 * - web push when the member has an active subscription (primary alert);
 * - plus email whenever we have an address (always included as backup).
 *
 * A member with neither is simply skipped. Centralizing the choice here keeps
 * every gig notification consistent without repeating the logic.
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
        }

        if (filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        return $channels;
    }
}
