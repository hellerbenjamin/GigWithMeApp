<?php

namespace App\Notifications;

use App\Models\Gig;
use App\Notifications\Concerns\RoutesToSmsAndMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioSmsMessage;

class GigConfirmed extends Notification implements ShouldQueue
{
    use Queueable, RoutesToSmsAndMail;

    public function __construct(public Gig $gig) {}

    /**
     * Get the SMS representation of the notification.
     */
    public function toTwilio(object $notifiable): TwilioSmsMessage
    {
        $gig = $this->gig->loadMissing('venue', 'band');

        $where = $gig->venue?->name ?? $gig->name;
        $when = $gig->date->format('D, M j');
        $start = $gig->start_time ? " at {$gig->start_time}" : '';

        return (new TwilioSmsMessage)
            ->content("{$gig->band->name}: your gig at {$where} on {$when}{$start} is confirmed.");
    }

    /**
     * Get the email representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $gig = $this->gig->loadMissing('venue', 'band');

        $where = $gig->venue?->name ?? $gig->name;
        $when = $gig->date->format('l, M j');
        $start = $gig->start_time ? " at {$gig->start_time}" : '';

        return (new MailMessage)
            ->subject("Confirmed: {$where} on {$gig->date->format('M j')}")
            ->greeting("Hi {$notifiable->name},")
            ->line("{$gig->band->name}'s gig at {$where} on {$when}{$start} is confirmed. See you there!")
            ->action('View the gig', route('gigs.show', $gig));
    }
}
