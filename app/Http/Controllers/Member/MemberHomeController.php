<?php

namespace App\Http\Controllers\Member;

use App\Enums\GigStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Rsvp\RsvpController;
use App\Models\Gig;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The member's personal, login-free home — reached via their durable push_token
 * (the same token doubles as the installed PWA's start_url, see the push_token
 * migration). Shows the member their upcoming gigs across every band they're in
 * and lets them turn on push. The unguessable token in the URL is the
 * authorization, mirroring the magic-link RSVP in {@see RsvpController}.
 */
class MemberHomeController extends Controller
{
    public function show(string $pushToken): Response
    {
        $member = User::where('push_token', $pushToken)->firstOrFail();

        $bandIds = $member->bands()->pluck('bands.id');

        $gigs = Gig::whereIn('band_id', $bandIds)
            ->whereDate('date', '>=', Carbon::today()->toDateString())
            ->where('status', '!=', GigStatusEnum::Cancelled->value)
            ->with(['venue:id,name', 'band:id,name'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(20)
            ->get()
            ->map(fn (Gig $gig) => [
                'id' => $gig->id,
                'name' => $gig->name,
                'band' => $gig->band->name,
                'venue' => $gig->venue?->name,
                'date' => $gig->date->format('Y-m-d'),
                'startTime' => $gig->start_time ? substr($gig->start_time, 0, 5) : null,
                'status' => $gig->status->value,
            ]);

        return Inertia::render('Member/Home', [
            'pushToken' => $pushToken,
            'memberName' => $member->name,
            'gigs' => $gigs,
        ]);
    }
}
