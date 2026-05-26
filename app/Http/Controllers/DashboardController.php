<?php

namespace App\Http\Controllers;

use App\Facades\ActiveBand;
use App\Models\Gig;
use App\Services\GigService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The active band's home screen: a few headline counts plus the next handful of
 * gigs. Everything is scoped to ActiveBand, which HasActiveBand guarantees.
 */
class DashboardController extends Controller
{
    public function index(GigService $gigs): Response
    {
        $band = ActiveBand::band();

        return Inertia::render('Dashboard', [
            'stats' => [
                'upcomingGigs' => $gigs->countUpcoming($band),
                'bookedThisMonth' => $gigs->bookedThisMonth($band),
                'currency' => $band->default_currency ?? 'USD',
                'venues' => $band->venues()->count(),
                'members' => $band->users()->count(),
            ],
            'upcomingGigs' => $gigs->getUpcomingGigs($band)
                ->map(fn (Gig $gig) => $this->present($gig)),
        ]);
    }

    /**
     * Flatten a gig to what the dashboard list shows. Date is a plain Y-m-d
     * string so the client formats it without a timezone shift; fee is raw so the
     * client formats it with the gig's own currency (mirrors the gigs index).
     *
     * @return array<string, mixed>
     */
    private function present(Gig $gig): array
    {
        return [
            'id' => $gig->id,
            'name' => $gig->name,
            'venue' => $gig->venue?->name,
            'date' => $gig->date->format('Y-m-d'),
            'status' => $gig->status->value,
            'fee' => $gig->fee !== null ? (float) $gig->fee : null,
            'currency' => $gig->currency,
        ];
    }
}
