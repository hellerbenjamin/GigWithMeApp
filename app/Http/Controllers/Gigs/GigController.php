<?php

namespace App\Http\Controllers\Gigs;

use App\Enums\BandUserRoleEnum;
use App\Enums\GigBookingModeEnum;
use App\Enums\GigResponseStatusEnum;
use App\Enums\GigStatusEnum;
use App\Enums\GigTypeEnum;
use App\Facades\ActiveBand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gigs\StoreGigRequest;
use App\Http\Requests\Gigs\UpdateGigRequest;
use App\Models\Gig;
use App\Models\GigMemberResponse;
use App\Services\GigBookingService;
use App\Services\GigService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GigController extends Controller
{
    /**
     * The active band's gig calendar.
     */
    public function index(GigService $gigs): Response
    {
        return Inertia::render('Gigs/Index', [
            'gigs' => $gigs->getBandGigs(ActiveBand::band())
                ->map(fn (Gig $gig) => $this->present($gig)),
        ]);
    }

    /**
     * A single gig's detail page. For poll-mode gigs this is where the per-member
     * poll progress lives, plus the admin "confirm anyway" / "re-poll" actions on
     * a poll that closed needing attention. Band-scoped — an off-band id 404s.
     */
    public function show(Gig $gig): Response
    {
        abort_unless($gig->band_id === ActiveBand::id(), 404);

        $isPoll = $gig->booking_mode === GigBookingModeEnum::Poll;

        if ($isPoll) {
            $gig->load(['venue', 'memberResponses.user']);
        } else {
            $gig->load('venue');
        }

        return Inertia::render('Gigs/Show', [
            'gig' => [
                'id' => $gig->id,
                'name' => $gig->name,
                'type' => $gig->type->value,
                'typeLabel' => $gig->type->label(),
                'status' => $gig->status->value,
                'statusLabel' => $gig->status->label(),
                'statusSeverity' => $gig->status->severity(),
                'bookingMode' => $gig->booking_mode->value,
                'bookingModeLabel' => $gig->booking_mode->label(),
                'date' => $gig->date->format('Y-m-d'),
                'venue' => $gig->venue?->name,
                'loadInTime' => $gig->load_in_time ? substr($gig->load_in_time, 0, 5) : null,
                'soundcheckTime' => $gig->soundcheck_time ? substr($gig->soundcheck_time, 0, 5) : null,
                'doorsTime' => $gig->doors_time ? substr($gig->doors_time, 0, 5) : null,
                'startTime' => $gig->start_time ? substr($gig->start_time, 0, 5) : null,
                'endTime' => $gig->end_time ? substr($gig->end_time, 0, 5) : null,
                'fee' => $gig->fee !== null ? (float) $gig->fee : null,
                'currency' => $gig->currency,
                'notes' => $gig->notes,
            ],
            // The poll block: per-member chips + the summary the admin acts on.
            // Null for auto-mode gigs, which have no responses to show.
            'poll' => $isPoll ? $this->presentPoll($gig) : null,
            // Owners/admins get the confirm-anyway / re-poll actions.
            'canManage' => $this->canManage(),
        ]);
    }

    /**
     * Manually confirm a poll-mode gig — the "confirm anyway" path after a poll
     * closes with someone unavailable. Owners/admins only; band-scoped.
     */
    public function confirm(Gig $gig, GigBookingService $booking): RedirectResponse
    {
        abort_unless($gig->band_id === ActiveBand::id(), 404);
        abort_unless($this->canManage(), 403);

        if ($gig->status !== GigStatusEnum::Confirmed) {
            $booking->confirm($gig);
        }

        $label = $gig->name ?: $gig->date->format('M j, Y');

        return to_route('gigs.show', $gig)->with('success', "{$label} is confirmed — the band's been notified.");
    }

    /**
     * Reopen a poll-mode gig's poll: wipe replies back to pending and ask
     * everyone again. Owners/admins only; band-scoped.
     */
    public function rePoll(Gig $gig, GigBookingService $booking): RedirectResponse
    {
        abort_unless($gig->band_id === ActiveBand::id(), 404);
        abort_unless($this->canManage(), 403);
        abort_unless($gig->booking_mode === GigBookingModeEnum::Poll, 404);

        $booking->rePoll($gig, request()->user());

        $label = $gig->name ?: $gig->date->format('M j, Y');

        return to_route('gigs.show', $gig)->with('success', "Re-polled {$label} — the band's been asked again.");
    }

    /**
     * Show the create-gig form.
     */
    public function create(): Response
    {
        $band = ActiveBand::band();

        return Inertia::render('Gigs/Create', [
            // The venue picker lists only this band's saved venues; venue is
            // optional, so an empty list still lets you book a TBD-venue gig.
            // Default time/notes columns are included so the form can pre-fill
            // the call sheet when a venue is selected.
            'venues' => $band->venues()->orderBy('name')->get([
                'id',
                'name',
                'default_load_in_time',
                'default_soundcheck_time',
                'default_doors_time',
                'default_start_time',
                'default_end_time',
                'default_notes',
            ]),
            'types' => $this->options(GigTypeEnum::cases()),
            'bookingModes' => GigBookingModeEnum::options(),
            'defaultBookingMode' => $band->default_booking_mode ?? GigBookingModeEnum::Auto->value,
            'defaultCurrency' => $band->default_currency ?? 'USD',
        ]);
    }

    /**
     * Persist a new gig for the active band.
     */
    public function store(StoreGigRequest $request, GigService $gigs, GigBookingService $booking): RedirectResponse
    {
        $gig = $gigs->createGig(ActiveBand::band(), $request->validated());

        // Auto mode confirms + notifies; poll mode opens the poll (the creator
        // is auto-marked available).
        $booking->applyMode($gig, $request->user());

        $label = $gig->name ?: $gig->date->format('M j, Y');
        $message = $gig->status === GigStatusEnum::Confirmed
            ? "{$label} is booked — the band's been notified."
            : "{$label} is on the calendar.";

        return to_route('gigs.index')->with('success', $message);
    }

    /**
     * Show the edit form for a gig. Scoped to the active band so one band can
     * never edit another's gig — an off-band id 404s rather than leaking it.
     */
    public function edit(Gig $gig): Response
    {
        abort_unless($gig->band_id === ActiveBand::id(), 404);

        $band = ActiveBand::band();

        return Inertia::render('Gigs/Edit', [
            'gig' => [
                'id' => $gig->id,
                'type' => $gig->type->value,
                'status' => $gig->status->value,
                'name' => $gig->name,
                'venueId' => $gig->venue_id,
                'date' => $gig->date->format('Y-m-d'),
                // Times come back as H:i:s from the time columns; the form's
                // pickers only care about hours and minutes.
                'loadInTime' => $gig->load_in_time ? substr($gig->load_in_time, 0, 5) : null,
                'soundcheckTime' => $gig->soundcheck_time ? substr($gig->soundcheck_time, 0, 5) : null,
                'doorsTime' => $gig->doors_time ? substr($gig->doors_time, 0, 5) : null,
                'startTime' => $gig->start_time ? substr($gig->start_time, 0, 5) : null,
                'endTime' => $gig->end_time ? substr($gig->end_time, 0, 5) : null,
                // decimal:2 casts to a string; hand the number picker a number.
                'fee' => $gig->fee !== null ? (float) $gig->fee : null,
                'currency' => $gig->currency,
                'notes' => $gig->notes,
            ],
            'venues' => $band->venues()->orderBy('name')->get(['id', 'name']),
            'types' => $this->options(GigTypeEnum::cases()),
            'statuses' => $this->options(GigStatusEnum::cases()),
        ]);
    }

    /**
     * Persist edits to a gig. Scoped to the active band for the same reason as
     * {@see self::edit()}.
     */
    public function update(UpdateGigRequest $request, Gig $gig, GigService $gigs): RedirectResponse
    {
        abort_unless($gig->band_id === ActiveBand::id(), 404);

        $gig = $gigs->updateGig($gig, $request->validated());

        $label = $gig->name ?: $gig->date->format('M j, Y');

        return to_route('gigs.index')->with('success', "{$label} was updated.");
    }

    /**
     * Remove a gig from the active band's calendar. Scoped to the active band so
     * one band can never delete another's gig — an off-band id 404s rather than
     * leaking its existence.
     */
    public function destroy(Gig $gig, GigService $gigs): RedirectResponse
    {
        abort_unless($gig->band_id === ActiveBand::id(), 404);

        $label = $gig->name ?: $gig->date->format('M j, Y');

        $gigs->deleteGig($gig);

        return to_route('gigs.index')->with('success', "{$label} was removed from the calendar.");
    }

    /**
     * Flatten a gig to the shape the calendar/list needs. Date is sent as a
     * plain Y-m-d string so the client formats it without a timezone shift.
     *
     * @return array<string, mixed>
     */
    private function present(Gig $gig): array
    {
        return [
            'id' => $gig->id,
            'name' => $gig->name,
            'type' => $gig->type->value,
            'status' => $gig->status->value,
            'statusLabel' => $gig->status->label(),
            'statusSeverity' => $gig->status->severity(),
            'date' => $gig->date->format('Y-m-d'),
            'venue' => $gig->venue?->name,
            'startTime' => $gig->start_time ? substr($gig->start_time, 0, 5) : null,
            'endTime' => $gig->end_time ? substr($gig->end_time, 0, 5) : null,
            'fee' => $gig->fee,
            'currency' => $gig->currency,
        ];
    }

    /**
     * The poll block for the detail page: a per-member row list plus the counts
     * the admin UI keys off (everyone replied? all available?). `closed` is the
     * needs-attention state — the poll settled but couldn't auto-confirm.
     *
     * @return array<string, mixed>
     */
    private function presentPoll(Gig $gig): array
    {
        $responses = $gig->memberResponses
            ->sortBy(fn (GigMemberResponse $r) => $r->user->name)
            ->values();

        $responded = $responses->whereNotNull('responded_at');

        return [
            'members' => $responses->map(fn (GigMemberResponse $r) => [
                'id' => $r->id,
                'name' => $r->user->name,
                'status' => $r->status->value,
                'statusLabel' => $r->status->label(),
                'severity' => $r->status->severity(),
                'critical' => (bool) $r->critical,
                'note' => $r->note,
                'respondedAt' => $r->responded_at?->diffForHumans(),
            ])->all(),
            'total' => $responses->count(),
            'respondedCount' => $responded->count(),
            'availableCount' => $responses
                ->where('status', GigResponseStatusEnum::Available)
                ->count(),
            'unavailableCount' => $responses
                ->where('status', GigResponseStatusEnum::Unavailable)
                ->count(),
            // Everyone replied but it couldn't auto-confirm — the admin's call.
            'closed' => $gig->poll_closed_at !== null,
        ];
    }

    /**
     * Whether the current user may run booking actions (confirm / re-poll) on the
     * active band — owners and admins. Mirrors the roster's management gate.
     */
    private function canManage(): bool
    {
        return in_array(
            ActiveBand::band()?->getUserRole(request()->user()),
            [BandUserRoleEnum::Owner, BandUserRoleEnum::Admin],
            true,
        );
    }

    /**
     * Shape a backed enum's cases as { value, label } for a PrimeVue Select.
     *
     * @param  array<int, GigTypeEnum|GigStatusEnum>  $cases
     * @return array<int, array{value: string, label: string}>
     */
    private function options(array $cases): array
    {
        return array_map(
            static fn ($case) => ['value' => $case->value, 'label' => $case->label()],
            $cases,
        );
    }
}
