<?php

namespace App\Http\Controllers;

use App\Enums\BandUserRoleEnum;
use App\Facades\ActiveBand;
use App\Models\Gig;
use App\Models\User;
use App\Services\GigService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The active band's home screen. Owners and admins see the full management
 * dashboard; plain members see a lighter view focused on upcoming gigs and
 * their own RSVP status. Both are scoped to ActiveBand.
 */
class DashboardController extends Controller
{
    public function index(GigService $gigs): Response
    {
        $band = ActiveBand::band();
        $user = auth()->user();
        $role = $band->getUserRole($user);
        $isManager = in_array($role, [BandUserRoleEnum::Owner, BandUserRoleEnum::Admin], true);

        if ($isManager) {
            return $this->renderManager($band, $gigs);
        }

        return $this->renderMember($band, $user, $gigs);
    }

    private function renderManager($band, GigService $gigs): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'upcomingGigs' => $gigs->countUpcoming($band),
                'bookedThisMonth' => $gigs->bookedThisMonth($band),
                'currency' => $band->default_currency ?? 'USD',
                'venues' => $band->venues()->count(),
                'members' => $band->users()->count(),
            ],
            'upcomingGigs' => $gigs->getUpcomingGigs($band)
                ->map(fn (Gig $gig) => $this->presentManager($gig)),
        ]);
    }

    private function renderMember($band, User $user, GigService $gigs): Response
    {
        $upcomingGigs = $gigs->getUpcomingGigs($band, 10)
            ->load('memberResponses')
            ->map(fn (Gig $gig) => $this->presentMember($gig, $user));

        return Inertia::render('MemberDashboard', [
            'stats' => [
                'upcomingGigs' => $gigs->countUpcoming($band),
            ],
            'upcomingGigs' => $upcomingGigs,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentManager(Gig $gig): array
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

    /**
     * Member view: date/name/venue/status + the member's own RSVP status for
     * poll-mode gigs. Fee and booking details are not exposed.
     *
     * @return array<string, mixed>
     */
    private function presentMember(Gig $gig, User $user): array
    {
        $myResponse = $gig->memberResponses
            ->firstWhere('user_id', $user->getKey());

        return [
            'id' => $gig->id,
            'name' => $gig->name,
            'venue' => $gig->venue?->name,
            'date' => $gig->date->format('Y-m-d'),
            'status' => $gig->status->value,
            'myRsvp' => $myResponse?->status->value,
            'myRsvpLabel' => $myResponse?->status->label(),
            'myRsvpSeverity' => $myResponse?->status->severity(),
        ];
    }
}
