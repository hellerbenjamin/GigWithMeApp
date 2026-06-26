<?php

use App\Console\Commands\SendGigReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Send gig reminders daily at 8 AM in the app timezone.
Schedule::command(SendGigReminders::class)->dailyAt('08:00');
