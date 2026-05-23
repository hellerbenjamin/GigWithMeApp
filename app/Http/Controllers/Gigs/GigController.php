<?php

namespace App\Http\Controllers\Gigs;

use App\Enums\GigStatusEnum;
use App\Enums\GigTypeEnum;
use App\Facades\ActiveBand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gigs\StoreGigRequest;
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
