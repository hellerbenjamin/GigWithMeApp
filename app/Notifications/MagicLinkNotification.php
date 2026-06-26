<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagicLinkNotification extends Notification
{
    public function __construct(private readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('login.magic.authenticate', ['token' => $this->token]);

        return (new MailMessage)
            ->subject('Your GigWithMe sign-in link')
            ->greeting("Hey {$notifiable->name},")
            ->line('Click below to sign in to GigWithMe. This link expires in 15 minutes and works once.')
            ->action('Sign in to GigWithMe', $url)
            ->line('If you didn\'t request this, you can ignore it.')
            ->salutation('GigWithMe');
    }
}
