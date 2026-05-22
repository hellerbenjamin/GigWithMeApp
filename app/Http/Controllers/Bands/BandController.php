<?php

namespace App\Http\Controllers\Bands;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bands\StoreBandRequest;
use App\Models\Genre;
use App\Services\BandService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BandController extends Controller
{
    /**
     * Show the create-band form.
     */
    public function create(): Response
    {
        return Inertia::render('Bands/Create', [
            // Existing genres power the type-ahead; users can also coin new ones.
            'genreSuggestions' => Genre::orderBy('name')->pluck('name'),
        ]);
    }

    /**
     * Create the band, make the user its owner, and switch them into it.
     */
    public function store(StoreBandRequest $request, BandService $bands): RedirectResponse
    {
        $band = $bands->createBand(
            $request->user(),
            $request->safe()->except('genres'),
            $request->validated('genres', []),
        );

        return to_route('dashboard')->with('success', "{$band->name} is ready — you're all set.");
    }
}
