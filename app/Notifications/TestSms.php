<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

/**
 * A throwaway SMS for smoke-testing Twilio credentials locally. Deliberately
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
        return [TwilioChannel::class];
    }

    public function toTwilio(object $notifiable): TwilioSmsMessage
    {
        return (new TwilioSmsMessage)->content($this->body);
    }
}
