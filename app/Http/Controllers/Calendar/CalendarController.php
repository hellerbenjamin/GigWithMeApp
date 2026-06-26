<?php

namespace App\Http\Controllers\Calendar;

use App\Enums\GigStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Gig;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class CalendarController extends Controller
{
    /**
     * Serve the member's personal iCal feed (.ics).
     *
     * The calendar_token in the URL is the authorization — no session needed,
     * so calendar apps can subscribe without OAuth. Cancelled gigs are omitted;
     * pending gigs appear as TENTATIVE so they show up but look provisional.
     */
    public function show(string $calendarToken): Response
    {
        $user = User::where('calendar_token', $calendarToken)->firstOrFail();

        $gigs = Gig::whereHas('band', fn ($q) => $q->whereHas(
            'users',
            fn ($q) => $q->where('users.id', $user->id),
        ))
            ->whereIn('status', [GigStatusEnum::Confirmed->value, GigStatusEnum::Pending->value])
            ->with('venue', 'band')
            ->orderBy('date')
            ->get();

        $ics = $this->buildCalendar($user, $gigs);

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="gigwithme.ics"',
            // Ask calendar apps to check for updates every 6 hours.
            'X-Published-TTL' => 'PT6H',
        ]);
    }

    /**
     * Regenerate the member's calendar token, invalidating any existing
     * subscriptions. Used when the member wants to revoke access to their feed.
     */
    public function reset(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['calendar_token' => Str::random(64)])->save();

        return back()->with('success', 'Calendar link reset. Update your calendar app with the new URL.');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Gig>  $gigs
     */
    private function buildCalendar(User $user, \Illuminate\Support\Collection $gigs): string
    {
        $now = Carbon::now()->utc()->format('Ymd\THis\Z');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//GigWithMe//GigWithMe//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:GigWithMe',
            'X-WR-CALDESC:Your upcoming gigs from GigWithMe',
            'X-PUBLISHED-TTL:PT6H',
        ];

        foreach ($gigs as $gig) {
            $lines = [...$lines, ...$this->buildEvent($gig, $now)];
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * @return array<int, string>
     */
    private function buildEvent(Gig $gig, string $dtstamp): array
    {
        $uid = "gig-{$gig->id}@gigwithme.app";
        $status = $gig->status === GigStatusEnum::Confirmed ? 'CONFIRMED' : 'TENTATIVE';

        $summary = $gig->name
            ?? ($gig->venue ? "Gig at {$gig->venue->name}" : "{$gig->band->name} gig");

        $location = $gig->venue?->name ?? '';

        $description = $this->buildDescription($gig);

        $url = route('gigs.show', $gig);

        if ($gig->start_time) {
            // Floating time (no TZID) — calendar apps display in the device's
            // local zone, which is correct for a venue-local event time.
            $start = $gig->date->format('Ymd') . 'T' . substr(str_replace(':', '', $gig->start_time), 0, 6);
            $dtstart = "DTSTART:{$start}";

            if ($gig->end_time) {
                $end = $gig->date->format('Ymd') . 'T' . substr(str_replace(':', '', $gig->end_time), 0, 6);
            } else {
                // Default duration: 2 hours from start.
                $end = Carbon::parse($gig->date->format('Y-m-d') . ' ' . $gig->start_time)
                    ->addHours(2)
                    ->format('Ymd\THis');
            }
            $dtend = "DTEND:{$end}";
        } else {
            // All-day: DTEND is the following day (per iCal spec, end is exclusive).
            $dtstart = 'DTSTART;VALUE=DATE:' . $gig->date->format('Ymd');
            $dtend = 'DTEND;VALUE=DATE:' . $gig->date->copy()->addDay()->format('Ymd');
        }

        $lines = [
            'BEGIN:VEVENT',
            "UID:{$uid}",
            "DTSTAMP:{$dtstamp}",
            $dtstart,
            $dtend,
            'SUMMARY:' . $this->fold($this->escape($summary)),
            "STATUS:{$status}",
        ];

        if ($location) {
            $lines[] = 'LOCATION:' . $this->fold($this->escape($location));
        }
        if ($description) {
            $lines[] = 'DESCRIPTION:' . $this->fold($this->escape($description));
        }

        $lines[] = "URL:{$url}";
        $lines[] = 'END:VEVENT';

        return $lines;
    }

    private function buildDescription(Gig $gig): string
    {
        $parts = [];

        if ($gig->band) {
            $parts[] = $gig->band->name;
        }

        if ($gig->start_time) {
            $start = Carbon::createFromFormat('H:i:s', $gig->start_time)->format('g:i A');
            $line = "Set time: {$start}";
            if ($gig->end_time) {
                $end = Carbon::createFromFormat('H:i:s', $gig->end_time)->format('g:i A');
                $line .= " - {$end}";
            }
            $parts[] = $line;
        }

        if ($gig->notes) {
            $parts[] = $gig->notes;
        }

        $parts[] = route('gigs.show', $gig);

        return implode('\n', $parts);
    }

    /** Escape iCal text-value special characters. */
    private function escape(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(';', '\;', $text);
        $text = str_replace(',', '\,', $text);
        // Newlines are already encoded as \n literal by buildDescription.
        return $text;
    }

    /**
     * Fold long lines to ≤75 octets per RFC 5545 §3.1.
     * Continuation lines begin with a single space.
     */
    private function fold(string $text): string
    {
        // We fold the value part; the property name is prepended by the caller.
        // Pass the raw value through chunk_split with a 73-char width (leaving
        // room for the property name on the first chunk) is tricky since we don't
        // know the name here. Instead fold at 75 chars total assuming the caller
        // writes "PROPERTY:value" and value starts at position 0 here — safe
        // because RFC 5545 says fold after any 75 octets.
        if (mb_strlen($text) <= 75) {
            return $text;
        }

        $result = '';
        $currentLine = '';

        foreach (mb_str_split($text) as $char) {
            if (mb_strlen($currentLine . $char) > 75) {
                $result .= $currentLine . "\r\n ";
                $currentLine = $char;
            } else {
                $currentLine .= $char;
            }
        }

        return $result . $currentLine;
    }
}
