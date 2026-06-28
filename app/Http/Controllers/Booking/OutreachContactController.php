<?php

namespace App\Http\Controllers\Booking;

use App\Enums\OutreachContactMethodEnum;
use App\Facades\ActiveBand;
use App\Http\Controllers\Controller;
use App\Models\OutreachContact;
use App\Models\VenueOutreach;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OutreachContactController extends Controller
{
    public function store(Request $request, VenueOutreach $outreach): RedirectResponse
    {
        $this->authorizeOutreach($outreach);

        $data = $request->validate([
            'occurred_on' => ['required', 'date'],
            'method'      => ['required', Rule::in(OutreachContactMethodEnum::values())],
            'summary'     => ['required', 'string', 'max:5000'],
            'response'    => ['nullable', 'string', 'max:5000'],
        ]);

        $outreach->contacts()->create($data);

        return back()->with('success', 'Contact logged.');
    }

    public function update(Request $request, OutreachContact $contact): RedirectResponse
    {
        $this->authorizeContact($contact);

        $data = $request->validate([
            'occurred_on' => ['required', 'date'],
            'method'      => ['required', Rule::in(OutreachContactMethodEnum::values())],
            'summary'     => ['required', 'string', 'max:5000'],
            'response'    => ['nullable', 'string', 'max:5000'],
        ]);

        $contact->update($data);

        return back()->with('success', 'Contact updated.');
    }

    public function destroy(OutreachContact $contact): RedirectResponse
    {
        $this->authorizeContact($contact);
        $contact->delete();

        return back()->with('success', 'Contact deleted.');
    }

    private function authorizeOutreach(VenueOutreach $outreach): void
    {
        $outreach->loadMissing('season');
        abort_unless($outreach->season->band_id === ActiveBand::id(), 404);
    }

    private function authorizeContact(OutreachContact $contact): void
    {
        $contact->loadMissing(['outreach.season']);
        abort_unless($contact->outreach->season->band_id === ActiveBand::id(), 404);
    }
}
