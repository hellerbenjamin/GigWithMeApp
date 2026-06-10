<?php

namespace App\Notifications;

use App\Models\Gig;
use App\Notifications\Concerns\RoutesGigNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\VonageMessage;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Tells a band's owners/admins that a gig poll has closed with everyone replied
 * but not everyone available — so it's their call to confirm, cancel, or
 * re-poll. Links to the gig's edit screen (behind auth).
 */
class GigPollNeedsAttention extends Notification implements ShouldQueue
{
    use Queueable, RoutesGigNotification;

    public function __construct(public Gig $gig) {}

    public function toVonage(object $notifiable): VonageMessage
    {
        $gig = $this->gig->loadMissing('venue', 'band');

        $where = $gig->venue?->name ?? ($gig->name ?: 'a gig');
        $when = $gig->date->format('D, M j');
        $link = route('gigs.edit', $gig);

        return (new VonageMessage)
            ->content("{$gig->band->name}: the band replied about {$where} on {$when}, but not everyone can make it. Your call: {$link}");
    }

    public function toMail(object $notifiable): MailMessage
    {
        $gig = $this->gig->loadMissing('venue', 'band');

        $where = $gig->venue?->name ?? ($gig->name ?: 'a gig');
        $when = $gig->date->format('l, M j');
        $link = route('gigs.edit', $gig);

        return (new MailMessage)
            ->subject("Your call: {$where} on {$gig->date->format('M j')}")
            ->greeting("Hi {$notifiable->name},")
            ->line("The band replied about {$where} on {$when}, but not everyone can make it. It's your call whether to confirm, cancel, or re-poll.")
            ->action('Review the gig', $link)
            ->line('Thanks for steering the ship.');
    }

    /**
     * Get the web push representation of the notification (admin-facing).
     */
    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        $gig = $this->gig->loadMissing('venue', 'band');

        $where = $gig->venue?->name ?? ($gig->name ?: 'a gig');
        $when = $gig->date->format('D, M j');

        return (new WebPushMessage)
            ->title("{$gig->band->name}: your call")
            ->body("Not everyone can make {$where} on {$when}. Confirm, cancel, or re-poll?")
            ->icon('/icons/icon-192.png')
            ->badge('/icons/badge-96.png')
            ->tag("gig-{$gig->id}")
            ->renotify()
            ->data(['url' => route('gigs.edit', $gig)]);
    }
}
