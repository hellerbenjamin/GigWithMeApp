<?php

namespace App\Notifications\Concerns;

use NotificationChannels\Twilio\TwilioChannel;

/**
 * Picks the delivery channels for a notifiable: SMS via Twilio when we have a
 * phone number, email when we have an address — both if both, neither (and so
 * a silent skip) if the member is unreachable. Lets every gig notification go
 * out over whichever channels a member has without each one repeating the
 * selection logic.
 */
trait RoutesToSmsAndMail
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return array_values(array_filter([
            filled($notifiable->phone_number ?? null) ? TwilioChannel::class : null,
            filled($notifiable->email ?? null) ? 'mail' : null,
        ]));
    }
}
