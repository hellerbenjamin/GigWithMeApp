<?php

namespace App\Console\Commands;

use App\Notifications\TestSms;
use Illuminate\Console\Command;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\Twilio\TwilioChannel;

/**
 * Smoke-test the Twilio SMS setup without the queue or the gig flow:
 *
 *   ddev artisan gigwithme:test-sms                 # sends to TWILIO_DEBUG_TO
 *   ddev artisan gigwithme:test-sms +19071234567    # or an explicit number
 *
 * Sends synchronously (notifyNow) and reports any error Twilio returns —
 * including the trial-account ones the channel otherwise swallows silently.
 */
class SendTestSms extends Command
{
    protected $signature = 'gigwithme:test-sms {to? : E.164 number; defaults to TWILIO_DEBUG_TO} {--body= : Custom message body}';

    protected $description = 'Send a one-off SMS through Twilio to verify local credentials.';

    public function handle(): int
    {
        $debugTo = config('twilio-notification-channel.debug_to');
        $from = config('twilio-notification-channel.from');
        $to = $this->argument('to') ?: $debugTo;

        if (! $to) {
            $this->error('No destination number. Pass one as an argument or set TWILIO_DEBUG_TO in .env.');

            return self::FAILURE;
        }

        if (! $from) {
            $this->warn('No TWILIO_FROM set — Twilio will reject the send unless a messaging service SID is configured.');
        }

        if ($debugTo) {
            $this->warn("debug_to is set ({$debugTo}); Twilio routes every message there regardless of recipient.");
        }

        // The channel swallows trial-account errors (unverified number, etc.),
        // so listen for the failure event to surface what actually happened.
        $failure = null;
        Event::listen(NotificationFailed::class, function (NotificationFailed $event) use (&$failure) {
            $failure = $event->data['message'] ?? 'unknown Twilio error';
        });

        $body = $this->option('body') ?: 'GigWithMe SMS smoke test — if you got this, Twilio is wired up. 🎸';

        $this->info("Sending to {$to}".($from ? " from {$from}" : '').' …');

        // notifyNow bypasses the queue so credentials are checked right here.
        Notification::route(TwilioChannel::class, $to)->notifyNow(new TestSms($body));

        if ($failure !== null) {
            $this->error("Twilio reported: {$failure}");
            $this->line('If this is a trial account, the destination must be added as a Verified Caller ID in the Twilio console.');

            return self::FAILURE;
        }

        $this->info('Twilio accepted the message with no error. Check the device.');

        return self::SUCCESS;
    }
}
