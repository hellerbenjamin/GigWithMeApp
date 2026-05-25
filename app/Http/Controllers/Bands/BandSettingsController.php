<?php

namespace App\Http\Controllers\Bands;

use App\Enums\BandUserRoleEnum;
use App\Facades\ActiveBand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bands\UpdateBandRequest;
use App\Models\Genre;
use App\Services\BandService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The active band's own settings — name, genres, contact details and
 * preferences. Any member may view the screen; editing is limited to owners
 * and admins (enforced here for the affordance and in {@see UpdateBandRequest}
 * for the write).
 */
class BandSettingsController extends Controller
{
    /**
     * Show the active band's settings form.
     */
    public function edit(): Response
    {
        $band = ActiveBand::band();

        return Inertia::render('Settings/Index', [
            'band' => [
                'name' => $band->name,
                'genres' => $band->genres()->orderBy('name')->pluck('name'),
                'hometown' => $band->hometown,
                'foundedYear' => $band->founded_year,
                'website' => $band->website,
                'email' => $band->email,
                'description' => $band->description,
                'defaultCurrency' => $band->default_currency ?? 'USD',
            ],
            // Existing genres power the type-ahead; users can also coin new ones.
            'genreSuggestions' => Genre::orderBy('name')->pluck('name'),
            'canManage' => $this->canManage(),
        ]);
    }

    /**
     * Save changes to the active band.
     */
    public function update(UpdateBandRequest $request, BandService $bands): RedirectResponse
    {
        $band = $bands->updateBand(
            ActiveBand::band(),
            $request->safe()->except('genres'),
            $request->validated('genres', []),
        );

        return to_route('settings.index')->with('success', "{$band->name}'s settings were saved.");
    }

    /**
     * Whether the current user may edit the active band's settings.
     */
    private function canManage(): bool
    {
        return in_array(
            ActiveBand::band()?->getUserRole(auth()->user()),
            [BandUserRoleEnum::Owner, BandUserRoleEnum::Admin],
            true,
        );
    }
}
