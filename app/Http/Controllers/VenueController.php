<?php

namespace App\Http\Controllers;

use App\Facades\ActiveBand;
use App\Http\Requests\Venues\StoreVenueRequest;
use App\Http\Requests\Venues\UpdateVenueRequest;
use App\Models\Venue;
use App\Services\VenueService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Venues for the active band. Reads/writes are scoped to ActiveBand throughout,
 * which the HasActiveBand middleware guarantees is present.
 */
class VenueController extends Controller
{
    /**
     * List the active band's venues, newest first.
     */
    public function index(): Response
    {
        $venues = ActiveBand::band()
            ->venues()
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'state', 'country', 'contact_person', 'contact_phone']);

        return Inertia::render('Venues/Index', [
            'venues' => $venues,
        ]);
    }

    /**
     * Show the create-venue form.
     */
    public function create(): Response
    {
        return Inertia::render('Venues/Create');
    }

    /**
     * Add a venue to the active band.
     */
    public function store(StoreVenueRequest $request, VenueService $venues): RedirectResponse
    {
        $venue = $venues->createVenue(ActiveBand::band(), $request->validated());

        return to_route('venues.index')->with('success', "{$venue->name} is in your venue book.");
    }

    /**
     * Show the edit form for a venue. Scoped to the active band so one band can
     * never edit another's venue — an off-band id 404s rather than leaking it.
     */
    public function edit(Venue $venue): Response
    {
        abort_unless($venue->band_id === ActiveBand::id(), 404);

        return Inertia::render('Venues/Edit', [
            'venue' => $venue->only([
                'id',
                'name',
                'address',
                'city',
                'state',
                'country',
                'postal_code',
                'phone',
                'email',
                'website',
                'contact_person',
                'contact_email',
                'contact_phone',
                'notes',
            ]),
        ]);
    }

    /**
     * Persist edits to a venue. Scoped to the active band for the same reason as
     * {@see self::edit()}.
     */
    public function update(UpdateVenueRequest $request, Venue $venue, VenueService $venues): RedirectResponse
    {
        abort_unless($venue->band_id === ActiveBand::id(), 404);

        $venue = $venues->updateVenue($venue, $request->validated());

        return to_route('venues.index')->with('success', "{$venue->name} was updated.");
    }

    /**
     * Remove a venue from the active band's book. Scoped to the active band so
     * one band can never delete another's venue — an off-band id 404s rather
     * than leaking its existence.
     */
    public function destroy(Venue $venue, VenueService $venues): RedirectResponse
    {
        abort_unless($venue->band_id === ActiveBand::id(), 404);

        $name = $venue->name;

        $venues->deleteVenue($venue);

        return to_route('venues.index')->with('success', "{$name} was removed from your venue book.");
    }
}
