<?php

namespace App\Notifications;

use App\Models\GigMemberResponse;
use App\Notifications\Concerns\RoutesToSmsAndMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioSmsMessage;

/**
 * Asks one band member whether they can play a poll-mode gig, linking to their
 * personal magic-link RSVP page. Carries the member's response row so the link
 * (and a future RCS button) can reference its token.
 */
class GigPollOpened extends Notification implements ShouldQueue
{
    use Queueable, RoutesToSmsAndMail;

    public function __construct(public GigMemberResponse $response) {}

    public function toTwilio(object $notifiable): TwilioSmsMessage
    {
        $gig = $this->response->gig->loadMissing('venue', 'band');

        $where = $gig->venue?->name ?? ($gig->name ?: 'a gig');
        $when = $gig->date->format('D, M j');
        $link = route('rsvp.show', $this->response->token);

        return (new TwilioSmsMessage)
            ->content("{$gig->band->name}: can you make {$where} on {$when}? Tap to reply — {$link}");
    }

    public function toMail(object $notifiable): MailMessage
    {
        $gig = $this->response->gig->loadMissing('venue', 'band');

        $where = $gig->venue?->name ?? ($gig->name ?: 'a gig');
        $when = $gig->date->format('l, M j');
        $link = route('rsvp.show', $this->response->token);

        return (new MailMessage)
            ->subject("{$gig->band->name}: can you make {$where} on {$gig->date->format('M j')}?")
            ->greeting("Hi {$notifiable->name},")
            ->line("{$gig->band->name} has a gig at {$where} on {$when}. Can you make it?")
            ->action('Tap to reply', $link)
            ->line('Thanks for keeping the band on the books!');
    }
}
