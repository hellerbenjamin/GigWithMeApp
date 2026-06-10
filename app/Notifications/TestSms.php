<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;

/**
 * A throwaway SMS for smoke-testing Vonage credentials locally. Deliberately
 * NOT queued so {@see \App\Console\Commands\SendTestSms} can verify a send
 * synchronously. Not used by the app itself.
 */
class TestSms extends Notification
{
    public function __construct(public string $body) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['vonage'];
    }

    public function toVonage(object $notifiable): VonageMessage
    {
        // unicode() so emoji and other multibyte characters send intact rather
        // than as a corrupted GSM-7 message.
        return (new VonageMessage)->content($this->body)->unicode();
    }
}
