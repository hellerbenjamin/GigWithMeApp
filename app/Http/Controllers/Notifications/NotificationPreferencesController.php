<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class NotificationPreferencesController extends Controller
{
    // The timing options members can pick from.
    public const AVAILABLE_DAYS = [7, 3, 1, 0];

    public function show(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Notifications/Preferences', [
            'channels' => $user->reminder_channels ?? ['email'],
            'days' => $user->reminder_days ?? [7, 1],
            'availableDays' => self::AVAILABLE_DAYS,
            'hasPush' => $user->hasPushSubscription(),
            'calendarUrl' => route('calendar.feed', ['calendarToken' => $user->ensureCalendarToken()]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'channels' => ['present', 'array'],
            'channels.*' => [Rule::in(['email', 'push'])],
            'days' => ['present', 'array'],
            'days.*' => [Rule::in(self::AVAILABLE_DAYS)],
        ]);

        $request->user()->update([
            'reminder_channels' => array_values(array_unique($data['channels'])),
            'reminder_days' => array_values(array_unique($data['days'])),
        ]);

        return back()->with('success', 'Notification preferences saved.');
    }
}
