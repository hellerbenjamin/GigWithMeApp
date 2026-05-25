<?php

namespace App\Notifications;

use App\Models\Gig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

/**
 * Tells a band's owners/admins that a gig poll has closed with everyone replied
 * but not everyone available — so it's their call to confirm, cancel, or
 * re-poll. Links to the gig's edit screen (behind auth).
 */
class GigPollNeedsAttention extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Gig $gig) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [TwilioChannel::class];
    }

    public function toTwilio(object $notifiable): TwilioSmsMessage
    {
        $gig = $this->gig->loadMissing('venue', 'band');

        $where = $gig->venue?->name ?? ($gig->name ?: 'a gig');
        $when = $gig->date->format('D, M j');
        $link = route('gigs.edit', $gig);

        return (new TwilioSmsMessage)
            ->content("{$gig->band->name}: the band replied about {$where} on {$when}, but not everyone can make it. Your call — {$link}");
    }
}
