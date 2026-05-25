<?php

namespace App\Notifications;

use App\Models\GigMemberResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

/**
 * Asks one band member whether they can play a poll-mode gig, linking to their
 * personal magic-link RSVP page. Carries the member's response row so the link
 * (and a future RCS button) can reference its token.
 */
class GigPollOpened extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public GigMemberResponse $response) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [TwilioChannel::class];
    }

    public function toTwilio(object $notifiable): TwilioSmsMessage
    {
        $gig = $this->response->gig->loadMissing('venue', 'band');

        $where = $gig->venue?->name ?? ($gig->name ?: 'a gig');
        $when = $gig->date->format('D, M j');
        $link = route('rsvp.show', $this->response->token);

        return (new TwilioSmsMessage)
            ->content("{$gig->band->name}: can you make {$where} on {$when}? Tap to reply — {$link}");
    }
}
