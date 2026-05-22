<?php

namespace App\Http\Controllers\Bands;

use App\Http\Controllers\Controller;
use App\Models\Band;
use App\Services\BandSessionService;
use Illuminate\Http\RedirectResponse;

/**
 * Switches the user's active band (POST /bands/{band}/set-active), the action
 * behind the BandSwitcher dropdown.
 */
class SetActiveBandController extends Controller
{
    public function __invoke(Band $band, BandSessionService $session): RedirectResponse
    {
        // A user may only activate a band they belong to. Scoping the lookup to
        // their memberships keeps this an existence check rather than a separate
        // policy — there's nothing to authorize beyond membership.
        abort_unless(
            auth()->user()->bands()->whereKey($band->getKey())->exists(),
            403,
        );

        $session->set($band);

        return back()->with('success', "Now working in {$band->name}.");
    }
}
