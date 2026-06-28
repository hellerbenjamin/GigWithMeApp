<?php

namespace App\Http\Controllers\Booking;

use App\Enums\OutreachContactMethodEnum;
use App\Enums\OutreachPriorityEnum;
use App\Enums\OutreachStatusEnum;
use App\Facades\ActiveBand;
use App\Http\Controllers\Controller;
use App\Models\BookingSeason;
use App\Models\VenueOutreach;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VenueOutreachController extends Controller
{
    public function store(Request $request, BookingSeason $season): RedirectResponse
    {
        abort_unless($season->band_id === ActiveBand::id(), 404);

        $data = $request->validate([
            'venue_id' => [
                'required', 'integer',
                Rule::exists('venues', 'id')->where('band_id', $season->band_id),
                Rule::unique('venue_outreach')->where('booking_season_id', $season->id),
            ],
            'priority' => ['nullable', Rule::in(OutreachPriorityEnum::values())],
        ]);

        $outreach = VenueOutreach::create([
            'booking_season_id' => $season->id,
            'venue_id'          => $data['venue_id'],
            'priority'          => $data['priority'] ?? OutreachPriorityEnum::Medium->value,
            'status'            => OutreachStatusEnum::Targeting->value,
        ]);

        return redirect()->route('booking.outreach.show', $outreach)
            ->with('success', 'Venue added to pipeline.');
    }

    public function show(VenueOutreach $outreach): Response
    {
        $this->authorizeOutreach($outreach);

        $outreach->load(['venue', 'season', 'contacts']);

        $allStatuses = collect(OutreachStatusEnum::cases())->map(fn ($s) => [
            'value' => $s->value,
            'label' => $s->label(),
        ]);

        $allPriorities = collect(OutreachPriorityEnum::cases())->map(fn ($p) => [
            'value' => $p->value,
            'label' => $p->label(),
        ]);

        $allMethods = collect(OutreachContactMethodEnum::cases())->map(fn ($m) => [
            'value' => $m->value,
            'label' => $m->label(),
        ]);

        return Inertia::render('Booking/OutreachDetail', [
            'outreach'     => $this->presentOutreach($outreach),
            'allStatuses'  => $allStatuses,
            'allPriorities'=> $allPriorities,
            'allMethods'   => $allMethods,
        ]);
    }

    public function update(Request $request, VenueOutreach $outreach): RedirectResponse
    {
        $this->authorizeOutreach($outreach);

        $data = $request->validate([
            'status'      => ['nullable', Rule::in(OutreachStatusEnum::values())],
            'priority'    => ['nullable', Rule::in(OutreachPriorityEnum::values())],
            'follow_up_on'=> ['nullable', 'date'],
            'notes'       => ['nullable', 'string', 'max:5000'],
        ]);

        $outreach->update(array_filter($data, fn ($v) => $v !== null));

        return back()->with('success', 'Updated.');
    }

    public function destroy(VenueOutreach $outreach): RedirectResponse
    {
        $this->authorizeOutreach($outreach);
        $seasonId = $outreach->booking_season_id;
        $outreach->delete();

        return redirect()->route('booking.seasons.show', $seasonId)
            ->with('success', 'Venue removed from pipeline.');
    }

    private function authorizeOutreach(VenueOutreach $outreach): void
    {
        $outreach->loadMissing('season');
        abort_unless($outreach->season->band_id === ActiveBand::id(), 404);
    }

    /** @return array<string, mixed> */
    private function presentOutreach(VenueOutreach $outreach): array
    {
        return [
            'id'              => $outreach->id,
            'seasonId'        => $outreach->booking_season_id,
            'seasonName'      => $outreach->season->name,
            'venueId'         => $outreach->venue->id,
            'venueName'       => $outreach->venue->name,
            'venueCity'       => $outreach->venue->city,
            'venueState'      => $outreach->venue->state,
            'venuePhone'      => $outreach->venue->phone,
            'venueEmail'      => $outreach->venue->email,
            'venueWebsite'    => $outreach->venue->website,
            'contactPerson'   => $outreach->venue->contact_person,
            'contactEmail'    => $outreach->venue->contact_email,
            'contactPhone'    => $outreach->venue->contact_phone,
            'status'          => $outreach->status->value,
            'statusLabel'     => $outreach->status->label(),
            'priority'        => $outreach->priority->value,
            'priorityLabel'   => $outreach->priority->label(),
            'followUpOn'      => $outreach->follow_up_on?->format('Y-m-d'),
            'notes'           => $outreach->notes,
            'contacts'        => $outreach->contacts->map(fn ($c) => [
                'id'         => $c->id,
                'occurredOn' => $c->occurred_on->format('Y-m-d'),
                'method'     => $c->method->value,
                'methodLabel'=> $c->method->label(),
                'summary'    => $c->summary,
                'response'   => $c->response,
            ]),
        ];
    }
}
