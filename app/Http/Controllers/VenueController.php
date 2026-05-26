<?php

namespace App\Http\Controllers;

use App\Facades\ActiveBand;
use App\Http\Requests\Venues\IndexVenuesRequest;
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
     * List the active band's venues, with search, filters, sort and paging. All
     * state rides the query string so the page is shareable and survives reload;
     * everything stays scoped to the active band.
     */
    public function index(IndexVenuesRequest $request): Response
    {
        $band = ActiveBand::band();
        $filters = $request->filters();
        [$sortColumn, $sortDirection] = IndexVenuesRequest::SORTS[$filters['sort']];

        $venues = $band->venues()
            // OR across the searchable columns, grouped so it can't escape the
            // band scope. caseSensitive:false → ilike on Postgres, lower()-like
            // elsewhere, keeping search case-insensitive and DB-portable.
            ->when($filters['search'], fn ($query, $search) => $query->where(function ($group) use ($search) {
                foreach (['name', 'city', 'state', 'contact_person'] as $column) {
                    $group->orWhereLike($column, "%{$search}%", caseSensitive: false);
                }
            }))
            ->when($filters['country'], fn ($query, $country) => $query->where('country', $country))
            ->when($filters['state'], fn ($query, $state) => $query->where('state', $state))
            ->when($filters['has_contact'], fn ($query) => $query->where(fn ($contact) => $contact
                ->whereNotNull('contact_person')
                ->orWhereNotNull('contact_phone')
            ))
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(12, ['id', 'name', 'city', 'state', 'country', 'contact_person', 'contact_phone'])
            ->withQueryString();

        return Inertia::render('Venues/Index', [
            'venues' => $venues,
            'filters' => $filters,
            'filterOptions' => [
                // Dropdown options are derived from the band's own data (location
                // is free-text). States cascade off the selected country.
                'countries' => $band->venues()
                    ->whereNotNull('country')->where('country', '!=', '')
                    ->distinct()->orderBy('country')->pluck('country'),
                'states' => $band->venues()
                    ->when($filters['country'], fn ($query, $country) => $query->where('country', $country))
                    ->whereNotNull('state')->where('state', '!=', '')
                    ->distinct()->orderBy('state')->pluck('state'),
            ],
            // Unfiltered existence — distinct from the filtered count, so the UI
            // can tell "no venues yet" apart from "nothing matches these filters".
            'hasVenues' => $band->venues()->exists(),
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
