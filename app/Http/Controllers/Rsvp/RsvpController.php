<?php

namespace App\Http\Controllers\Rsvp;

use App\Enums\GigResponseStatusEnum;
use App\Enums\GigStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\GigMemberResponse;
use App\Services\GigBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The guest-facing magic-link RSVP: a member taps the link in their SMS and
 * marks themselves available / not for a poll-mode gig — no login required. The
 * unguessable token in the URL is the authorization. See docs/gig-booking-flow.md.
 */
class RsvpController extends Controller
{
    /**
     * Show the RSVP page for a response token.
     */
    public function show(string $token): Response
    {
        $response = GigMemberResponse::with(['gig.venue', 'gig.band', 'user'])
            ->where('token', $token)
            ->firstOrFail();

        $gig = $response->gig;

        return Inertia::render('Rsvp/Show', [
            'token' => $token,
            // The member's durable push token powers login-free push opt-in
            // right here on the RSVP page (see PushOptIn.vue).
            'pushToken' => $response->user->ensurePushToken(),
            'memberName' => $response->user->name,
            'bandName' => $gig->band->name,
            'gig' => [
                'name' => $gig->name,
                'date' => $gig->date->format('l, F j, Y'),
                'venue' => $gig->venue?->name,
                'startTime' => $gig->start_time ? substr($gig->start_time, 0, 5) : null,
            ],
            'response' => [
                'status' => $response->status->value,
                'note' => $response->note,
            ],
            // Once the gig leaves "pending" the poll is settled — show the
            // outcome read-only rather than letting a late reply change it.
            'closed' => $gig->status !== GigStatusEnum::Pending,
            'gigStatus' => $gig->status->value,
        ]);
    }

    /**
     * Record the member's reply.
     */
    public function update(Request $request, string $token, GigBookingService $booking): RedirectResponse
    {
        $data = $request->validate([
            'available' => ['required', 'boolean'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $response = GigMemberResponse::with('gig')->where('token', $token)->firstOrFail();

        // The poll is settled — don't let a late reply rewrite a decided gig.
        if ($response->gig->status !== GigStatusEnum::Pending) {
            return back()->with('info', 'This gig has already been settled — thanks anyway!');
        }

        $booking->recordResponse(
            $response,
            $data['available'] ? GigResponseStatusEnum::Available : GigResponseStatusEnum::Unavailable,
            'web',
            $data['note'] ?? null,
        );

        return back()->with('success', 'Thanks — your reply is in.');
    }

    /**
     * Record a reply from a push-notification action button. Same funnel as
     * {@see self::update()}, but called by the service worker (no page), so it
     * answers JSON and records the 'push' channel.
     */
    public function reply(Request $request, string $token, GigBookingService $booking): JsonResponse
    {
        $data = $request->validate([
            'available' => ['required', 'boolean'],
        ]);

        $response = GigMemberResponse::with('gig')->where('token', $token)->firstOrFail();

        if ($response->gig->status !== GigStatusEnum::Pending) {
            return response()->json(['settled' => true]);
        }

        $booking->recordResponse(
            $response,
            $data['available'] ? GigResponseStatusEnum::Available : GigResponseStatusEnum::Unavailable,
            'push',
        );

        return response()->json(['ok' => true]);
    }
}
