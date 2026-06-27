<?php

namespace App\Notifications\Concerns;

use App\Models\MobilePushToken;
use App\Notifications\Channels\MobilePushChannel;
use NotificationChannels\WebPush\WebPushChannel;

/**
 * Picks the delivery channels for a gig notification:
 *
 * - mobile push when the member has a registered Expo device token;
 * - web push when the member has an active browser push subscription;
 * - email whenever we have an address (always included as backup).
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

        if (MobilePushToken::where('user_id', $notifiable->getKey())->exists()) {
            $channels[] = MobilePushChannel::class;
        }

        if (method_exists($notifiable, 'hasPushSubscription') && $notifiable->hasPushSubscription()) {
            $channels[] = WebPushChannel::class;
        }

        if (filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        return $channels;
    }
}
