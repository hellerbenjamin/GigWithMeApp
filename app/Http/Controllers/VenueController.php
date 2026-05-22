<?php

namespace App\Http\Controllers;

use App\Facades\ActiveBand;
use App\Http\Requests\Venues\StoreVenueRequest;
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
}
