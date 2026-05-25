<?php

namespace App\Notifications;

use App\Models\Gig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class GigConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Gig $gig) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [TwilioChannel::class];
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toTwilio(object $notifiable): TwilioSmsMessage
    {
        $gig = $this->gig->loadMissing('venue');

        $where = $gig->venue?->name ?? $gig->name;
        $when = $gig->date->format('D, M j');
        $start = $gig->start_time ? " at {$gig->start_time}" : '';

        return (new TwilioSmsMessage)
            ->content("Roadie: your gig at {$where} on {$when}{$start} is confirmed.");
    }
}
