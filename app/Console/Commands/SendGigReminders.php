<?php

namespace App\Console\Commands;

use App\Enums\GigStatusEnum;
use App\Models\Gig;
use App\Models\GigReminderLog;
use App\Notifications\GigReminder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('reminders:send')]
#[Description('Send gig reminder notifications to band members based on their preferences.')]
class SendGigReminders extends Command
{
    public function handle(): int
    {
        // Collect every distinct days_before value configured by any user so we
        // only query gig dates that someone actually wants a reminder for.
        $activeDays = $this->activeDayOffsets();

        if ($activeDays->isEmpty()) {
            $this->info('No reminder preferences configured — nothing to send.');
            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($activeDays as $days) {
            $targetDate = Carbon::today()->addDays($days)->toDateString();

            $gigs = Gig::where('date', $targetDate)
                ->where('status', GigStatusEnum::Confirmed->value)
                ->with(['band.users'])
                ->get();

            foreach ($gigs as $gig) {
                foreach ($gig->band->users as $member) {
                    $wantsThis = in_array($days, $member->reminder_days ?? [], true);
                    if (! $wantsThis) {
                        continue;
                    }

                    $alreadySent = GigReminderLog::where([
                        'gig_id' => $gig->id,
                        'user_id' => $member->id,
                        'days_before' => $days,
                    ])->exists();

                    if ($alreadySent) {
                        continue;
                    }

                    $member->notify(new GigReminder($gig, $days));

                    GigReminderLog::create([
                        'gig_id' => $gig->id,
                        'user_id' => $member->id,
                        'days_before' => $days,
                        'sent_at' => now(),
                    ]);

                    $sent++;
                }
            }
        }

        $this->info("Sent {$sent} reminder(s).");

        return self::SUCCESS;
    }

    /**
     * All unique days_before values present in any user's reminder_days column.
     * Avoids loading every user just to find what dates to scan.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function activeDayOffsets(): \Illuminate\Support\Collection
    {
        return \App\Models\User::whereNotNull('reminder_days')
            ->whereJsonLength('reminder_days', '>', 0)
            ->pluck('reminder_days')
            ->flatten()
            ->unique()
            ->values();
    }
}
