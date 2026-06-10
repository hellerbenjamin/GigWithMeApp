<?php

namespace App\Notifications;

use App\Models\GigMemberResponse;
use App\Notifications\Concerns\RoutesGigNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\VonageMessage;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Asks one band member whether they can play a poll-mode gig, linking to their
 * personal magic-link RSVP page. Carries the member's response row so the link
 * (and a future RCS button) can reference its token.
 */
class GigPollOpened extends Notification implements ShouldQueue
{
    use Queueable, RoutesGigNotification;

    public function __construct(public GigMemberResponse $response) {}

    public function toVonage(object $notifiable): VonageMessage
    {
        $gig = $this->response->gig->loadMissing('venue', 'band');

        $where = $gig->venue?->name ?? ($gig->name ?: 'a gig');
        $when = $gig->date->format('D, M j');
        $link = route('rsvp.show', $this->response->token);

        return (new VonageMessage)
            ->content("{$gig->band->name}: can you make {$where} on {$when}? Tap to reply: {$link}");
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

    /**
     * Get the web push representation — an actionable notification the member can
     * answer ("I'm in" / "Can't make it") without opening the app. The service
     * worker records the reply via the response token carried in data.
     */
    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        $gig = $this->response->gig->loadMissing('venue', 'band');

        $where = $gig->venue?->name ?? ($gig->name ?: 'a gig');
        $when = $gig->date->format('D, M j');

        return (new WebPushMessage)
            ->title("{$gig->band->name}: can you make it?")
            ->body("{$where} on {$when}. Can you play?")
            ->icon('/icons/icon-192.png')
            ->badge('/icons/badge-96.png')
            ->tag("gig-{$gig->id}")
            ->renotify()
            ->action("I'm in", 'rsvp-yes')
            ->action("Can't make it", 'rsvp-no')
            ->data([
                'url' => route('rsvp.show', $this->response->token),
                'replyToken' => $this->response->token,
            ]);
    }
}
