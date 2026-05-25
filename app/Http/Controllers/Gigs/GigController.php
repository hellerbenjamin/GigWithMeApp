<?php

namespace App\Http\Controllers\Gigs;

use App\Enums\GigStatusEnum;
use App\Enums\GigTypeEnum;
use App\Facades\ActiveBand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gigs\StoreGigRequest;
use App\Http\Requests\Gigs\UpdateGigRequest;
use App\Models\Gig;
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
     * Show the create-gig form.
     */
    public function create(): Response
    {
        $band = ActiveBand::band();

        return Inertia::render('Gigs/Create', [
            // The venue picker lists only this band's saved venues; venue is
            // optional, so an empty list still lets you book a TBD-venue gig.
            'venues' => $band->venues()->orderBy('name')->get(['id', 'name']),
            'types' => $this->options(GigTypeEnum::cases()),
            'statuses' => $this->options(GigStatusEnum::cases()),
            'defaultCurrency' => $band->default_currency ?? 'USD',
        ]);
    }

    /**
     * Persist a new gig for the active band.
     */
    public function store(StoreGigRequest $request, GigService $gigs): RedirectResponse
    {
        $gig = $gigs->createGig(ActiveBand::band(), $request->validated());

        $label = $gig->name ?: $gig->date->format('M j, Y');

        return to_route('gigs.index')->with('success', "{$label} is on the calendar.");
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
