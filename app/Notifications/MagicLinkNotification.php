<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagicLinkNotification extends Notification
{
    /**
     * @param  bool  $mobile  When true, the link opens the mobile app via the
     *                        gigwithme:// scheme instead of the web session.
     */
    public function __construct(
        private readonly string $token,
        private readonly bool $mobile = false,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = $this->mobile
            ? route('login.magic.mobile', ['token' => $this->token])
            : route('login.magic.authenticate', ['token' => $this->token]);

        return (new MailMessage)
            ->subject('Your GigWithMe sign-in link')
            ->greeting("Hey {$notifiable->name},")
            ->line('Click below to sign in to GigWithMe. This link expires in 15 minutes and works once.')
            ->action('Sign in to GigWithMe', $url)
            ->line('If you didn\'t request this, you can ignore it.')
            ->salutation('GigWithMe');
    }
}
