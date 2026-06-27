<?php

namespace App\Http\Controllers\Api\Member;

use App\Enums\GigResponseStatusEnum;
use App\Enums\GigStatusEnum;
use App\Http\Controllers\Api\ApiController;
use App\Models\Gig;
use App\Models\GigMemberResponse;
use App\Services\GigBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class GigController extends ApiController
{
    /**
     * Upcoming gigs across all bands the authenticated member belongs to.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $bandIds = $user->bands()->pluck('bands.id');

        $gigs = Gig::whereIn('band_id', $bandIds)
            ->whereDate('date', '>=', Carbon::today()->toDateString())
            ->where('status', '!=', GigStatusEnum::Cancelled->value)
            ->with(['venue:id,name', 'band:id,name,slug'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(50)
            ->get()
            ->map(fn (Gig $gig) => $this->gigSummary($gig));

        return response()->json(['data' => $gigs]);
    }

    /**
     * Single gig with full detail and the member's own RSVP status.
     */
    public function show(Request $request, Gig $gig): JsonResponse
    {
        $user = $request->user();

        // Gate: member must belong to the gig's band.
        abort_unless(
            $user->bands()->where('bands.id', $gig->band_id)->exists(),
            403,
        );

        $gig->load(['venue', 'band:id,name,slug']);

        $rsvp = GigMemberResponse::where('gig_id', $gig->id)
            ->where('user_id', $user->id)
            ->first();

        return response()->json([
            'data' => [
                ...$this->gigSummary($gig),
                'load_in_time'    => $gig->load_in_time ? substr($gig->load_in_time, 0, 5) : null,
                'soundcheck_time' => $gig->soundcheck_time ? substr($gig->soundcheck_time, 0, 5) : null,
                'doors_time'      => $gig->doors_time ? substr($gig->doors_time, 0, 5) : null,
                'end_time'        => $gig->end_time ? substr($gig->end_time, 0, 5) : null,
                'fee'             => $gig->fee,
                'currency'        => $gig->currency,
                'notes'           => $gig->notes,
                'venue'           => $gig->venue ? [
                    'id'   => $gig->venue->id,
                    'name' => $gig->venue->name,
                ] : null,
                'rsvp'            => $rsvp ? [
                    'status'       => $rsvp->status->value,
                    'note'         => $rsvp->note,
                    'responded_at' => $rsvp->responded_at?->toIso8601String(),
                    'open'         => $gig->status === GigStatusEnum::Pending,
                ] : null,
            ],
        ]);
    }

    /**
     * Record the authenticated member's RSVP for a poll-mode gig.
     */
    public function rsvp(Request $request, Gig $gig, GigBookingService $booking): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $user->bands()->where('bands.id', $gig->band_id)->exists(),
            403,
        );

        if ($gig->status !== GigStatusEnum::Pending) {
            return response()->json(['message' => 'This poll is no longer open.'], 422);
        }

        $data = $request->validate([
            'available' => ['required', 'boolean'],
            'note'      => ['nullable', 'string', 'max:500'],
        ]);

        $response = GigMemberResponse::where('gig_id', $gig->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $booking->recordResponse(
            $response,
            $data['available'] ? GigResponseStatusEnum::Available : GigResponseStatusEnum::Unavailable,
            'mobile',
            $data['note'] ?? null,
        );

        $response->refresh();

        return response()->json([
            'data' => [
                'status'       => $response->status->value,
                'note'         => $response->note,
                'responded_at' => $response->responded_at?->toIso8601String(),
                'open'         => $gig->fresh()->status === GigStatusEnum::Pending,
            ],
        ]);
    }

    private function gigSummary(Gig $gig): array
    {
        return [
            'id'         => $gig->id,
            'name'       => $gig->name,
            'status'     => $gig->status->value,
            'date'       => $gig->date->format('Y-m-d'),
            'start_time' => $gig->start_time ? substr($gig->start_time, 0, 5) : null,
            'band'       => [
                'id'   => $gig->band->id,
                'name' => $gig->band->name,
                'slug' => $gig->band->slug,
            ],
            'venue_name' => $gig->venue?->name,
        ];
    }
}
