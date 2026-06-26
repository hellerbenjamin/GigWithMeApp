<?php

namespace App\Notifications;

use App\Models\Gig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class GigReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Gig $gig,
        public readonly int $daysBefore,
    ) {}

    /**
     * Route via the member's own channel preferences, capped by capability:
     * push only fires if they have an active subscription.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $preferred = $notifiable->reminder_channels ?? ['email'];
        $channels = [];

        if (
            in_array('push', $preferred, true)
            && method_exists($notifiable, 'hasPushSubscription')
            && $notifiable->hasPushSubscription()
        ) {
            $channels[] = WebPushChannel::class;
        }

        if (in_array('email', $preferred, true) && filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $gig = $this->gig->loadMissing('venue', 'band');

        $when = $gig->date->format('l, M j');
        $where = $gig->venue?->name ?? $gig->name;
        $start = $gig->start_time
            ? \Carbon\Carbon::createFromFormat('H:i:s', $gig->start_time)->format('g:i A')
            : null;

        $subject = $this->daysBefore === 1
            ? "Reminder: {$where} is tomorrow"
            : "Reminder: {$where} in {$this->daysBefore} days";

        $body = "{$gig->band->name} has a gig at {$where} on {$when}";
        if ($start) {
            $body .= " starting at {$start}";
        }
        $body .= '.';

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Hi {$notifiable->name},")
            ->line($body)
            ->action('View gig details', route('gigs.show', $gig));
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        $gig = $this->gig->loadMissing('venue', 'band');

        $where = $gig->venue?->name ?? $gig->name;
        $when = $gig->date->format('D, M j');

        $title = $this->daysBefore === 1
            ? "{$gig->band->name}: gig tomorrow"
            : "{$gig->band->name}: gig in {$this->daysBefore} days";

        $body = $where;
        if ($gig->start_time) {
            $start = \Carbon\Carbon::createFromFormat('H:i:s', $gig->start_time)->format('g:i A');
            $body .= " · {$start}";
        }
        $body .= " · {$when}";

        return (new WebPushMessage)
            ->title($title)
            ->body($body)
            ->icon('/icons/icon-192.png')
            ->badge('/icons/badge-96.png')
            ->tag("reminder-gig-{$gig->id}-{$this->daysBefore}")
            ->data(['url' => route('gigs.show', $gig)]);
    }
}
