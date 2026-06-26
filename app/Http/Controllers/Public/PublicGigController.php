<?php

namespace App\Http\Controllers\Public;

use App\Enums\GigStatusEnum;
use App\Enums\GigTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Band;
use App\Models\Gig;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public gig listing for a band — shareable with anyone, no login required.
 * Shows only confirmed gigs of type 'gig' (not rehearsals). Fee, notes,
 * poll data, and internal booking details are never exposed.
 */
class PublicGigController extends Controller
{
    public function index(string $slug): Response
    {
        $band = Band::where('slug', $slug)->firstOrFail();

        $gigs = $band->gigs()
            ->where('status', GigStatusEnum::Confirmed)
            ->where('type', GigTypeEnum::Gig)
            ->with('venue')
            ->orderBy('date')
            ->get()
            ->map(fn (Gig $gig) => [
                'id' => $gig->id,
                'name' => $gig->name,
                'date' => $gig->date->toDateString(),
                'start_time' => $gig->start_time
                    ? \Carbon\Carbon::createFromFormat('H:i:s', $gig->start_time)->format('g:i A')
                    : null,
                'venue' => $gig->venue ? [
                    'name' => $gig->venue->name,
                    'city' => $gig->venue->city,
                    'state' => $gig->venue->state,
                ] : null,
            ]);

        $today = now()->toDateString();

        return Inertia::render('Public/BandGigs', [
            'band' => [
                'name' => $band->name,
                'slug' => $band->slug,
                'hometown' => $band->hometown,
                'website' => $band->website,
            ],
            'upcoming' => $gigs->filter(fn ($g) => $g['date'] >= $today)->values(),
            'past' => $gigs->filter(fn ($g) => $g['date'] < $today)->sortByDesc('date')->values(),
        ]);
    }
}
