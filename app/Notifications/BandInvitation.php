<?php

namespace App\Notifications;

use App\Models\Band;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Emails a newly added member a link to their acceptance page, where they
 * confirm their own mobile number and opt in to gig texts. Email is the
 * deliberate first touch: no SMS is ever sent before the member has opted in
 * themselves (see docs/sms-consent-invite-flow.md). Queued like the gig
 * notifications so the add-member request returns immediately.
 */
class BandInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Band $band) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $accept = route('invite.accept', $notifiable->ensureInviteToken());

        return (new MailMessage)
            ->subject("You're invited to join {$this->band->name} on GigWithMe")
            ->greeting("Hi {$notifiable->name},")
            ->line("{$this->band->name} added you to their roster on GigWithMe, the app a band uses to rally its people and keep its dates on the books.")
            ->line('Accept your invitation to set up how you want to hear about gigs. You can add your mobile number for text alerts (optional) or just stick with email.')
            ->action('Accept your invitation', $accept)
            ->line('If you weren\'t expecting this, you can safely ignore this email.');
    }
}
