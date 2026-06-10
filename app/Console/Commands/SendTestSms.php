<?php

namespace App\Console\Commands;

use App\Notifications\TestSms;
use Illuminate\Console\Command;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

/**
 * Smoke-test the Vonage SMS setup without the queue or the gig flow:
 *
 *   ddev artisan gigwithme:test-sms                 # sends to VONAGE_DEBUG_TO
 *   ddev artisan gigwithme:test-sms +19071234567    # or an explicit number
 *
 * Sends synchronously (notifyNow) and reports any error Vonage returns.
 */
class SendTestSms extends Command
{
    protected $signature = 'sms:test {to? : E.164 number; defaults to VONAGE_DEBUG_TO} {--body= : Custom message body}';

    protected $description = 'Send a one-off SMS through Vonage to verify local credentials.';

    public function handle(): int
    {
        $debugTo = config('services.vonage.debug_to');
        $from = config('services.vonage.sms_from');
        $to = $this->argument('to') ?: $debugTo;

        if (! $to) {
            $this->error('No destination number. Pass one as an argument or set VONAGE_DEBUG_TO in .env.');

            return self::FAILURE;
        }

        if (! $from) {
            $this->warn('No VONAGE_SMS_FROM set — Vonage will reject the send without a sender ID or number.');
        }

        if ($debugTo) {
            $this->warn("debug_to is set ({$debugTo}); every message routes there regardless of recipient.");
        }

        // The channel reports API failures via the NotificationFailed event
        // rather than throwing here, so listen for it to surface what happened.
        $failure = null;
        Event::listen(NotificationFailed::class, function (NotificationFailed $event) use (&$failure) {
            $failure = $event->data['message'] ?? 'unknown Vonage error';
        });

        $body = $this->option('body') ?: 'GigWithMe SMS smoke test: if you got this, Vonage is wired up. 🎸';

        $this->info("Sending to {$to}".($from ? " from {$from}" : '').' …');

        // notifyNow bypasses the queue so credentials are checked right here.
        Notification::route('vonage', $to)->notifyNow(new TestSms($body));

        if ($failure !== null) {
            $this->error("Vonage reported: {$failure}");
            $this->line('Check VONAGE_KEY / VONAGE_SECRET and that VONAGE_SMS_FROM is a sender you own.');

            return self::FAILURE;
        }

        $this->info('Vonage accepted the message with no error. Check the device.');

        return self::SUCCESS;
    }
}
