<?php

namespace App\Http\Controllers\Booking;

use App\Enums\OutreachStatusEnum;
use App\Facades\ActiveBand;
use App\Http\Controllers\Controller;
use App\Models\BookingSeason;
use App\Models\VenueOutreach;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingSeasonController extends Controller
{
    public function index(): Response
    {
        $band = ActiveBand::band();

        $seasons = BookingSeason::where('band_id', $band->id)
            ->withCount('venueOutreach')
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->get()
            ->map(fn (BookingSeason $s) => $this->presentSeason($s));

        return Inertia::render('Booking/Seasons', [
            'seasons' => $seasons,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:120'],
            'starts_on'  => ['nullable', 'date'],
            'ends_on'    => ['nullable', 'date', 'after_or_equal:starts_on'],
            'notes'      => ['nullable', 'string', 'max:2000'],
        ]);

        $season = BookingSeason::create([
            ...$data,
            'band_id' => ActiveBand::id(),
        ]);

        return redirect()->route('booking.seasons.show', $season)
            ->with('success', "Season \"{$season->name}\" created.");
    }

    public function show(BookingSeason $season): Response
    {
        abort_unless($season->band_id === ActiveBand::id(), 404);

        $season->load(['venueOutreach.venue', 'venueOutreach.contacts']);

        // Group outreach records by status for the kanban board.
        $columns = collect(OutreachStatusEnum::cases())->mapWithKeys(fn ($status) => [
            $status->value => [
                'label'    => $status->label(),
                'status'   => $status->value,
                'terminal' => $status->isTerminal(),
                'items'    => [],
            ],
        ]);

        foreach ($season->venueOutreach as $outreach) {
            $col = $outreach->status->value;
            $columns[$col]['items'][] = $this->presentOutreach($outreach);
        }

        // All seasons for this band (for the season switcher and carry-forward dialog).
        $allSeasons = BookingSeason::where('band_id', $season->band_id)
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->get(['id', 'name', 'starts_on']);

        // Venues not yet in this season's pipeline (for the add-venue dropdown).
        $existingVenueIds = $season->venueOutreach->pluck('venue_id');
        $availableVenues = ActiveBand::band()
            ->venues()
            ->whereNotIn('id', $existingVenueIds)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'state']);

        return Inertia::render('Booking/Roadmap', [
            'season'          => $this->presentSeason($season),
            'allSeasons'      => $allSeasons->map(fn ($s) => [
                'id'        => $s->id,
                'name'      => $s->name,
                'starts_on' => $s->starts_on?->format('Y-m-d'),
            ]),
            'columns'         => $columns->values(),
            'availableVenues' => $availableVenues->map(fn ($v) => [
                'id'    => $v->id,
                'name'  => $v->name,
                'city'  => $v->city,
                'state' => $v->state,
            ]),
        ]);
    }

    public function update(Request $request, BookingSeason $season): RedirectResponse
    {
        abort_unless($season->band_id === ActiveBand::id(), 404);

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:120'],
            'starts_on' => ['nullable', 'date'],
            'ends_on'   => ['nullable', 'date', 'after_or_equal:starts_on'],
            'notes'     => ['nullable', 'string', 'max:2000'],
        ]);

        $season->update($data);

        return back()->with('success', 'Season updated.');
    }

    public function destroy(BookingSeason $season): RedirectResponse
    {
        abort_unless($season->band_id === ActiveBand::id(), 404);

        $name = $season->name;
        $season->delete();

        return redirect()->route('booking.seasons.index')
            ->with('success', "Season \"{$name}\" deleted.");
    }

    /**
     * Copy selected venues from a prior season into this season as "targeting".
     * Venues already in this season are silently skipped.
     */
    public function carryForward(Request $request, BookingSeason $season): RedirectResponse
    {
        abort_unless($season->band_id === ActiveBand::id(), 404);

        $data = $request->validate([
            'from_season_id'       => ['required', 'integer'],
            'venue_outreach_ids'   => ['required', 'array', 'min:1'],
            'venue_outreach_ids.*' => ['integer'],
        ]);

        $source = BookingSeason::where('id', $data['from_season_id'])
            ->where('band_id', $season->band_id)
            ->firstOrFail();

        $sourceOutreach = VenueOutreach::where('booking_season_id', $source->id)
            ->whereIn('id', $data['venue_outreach_ids'])
            ->get();

        $existingVenueIds = VenueOutreach::where('booking_season_id', $season->id)
            ->pluck('venue_id')
            ->flip();

        $added = 0;
        foreach ($sourceOutreach as $outreach) {
            if ($existingVenueIds->has($outreach->venue_id)) {
                continue;
            }
            VenueOutreach::create([
                'booking_season_id' => $season->id,
                'venue_id'          => $outreach->venue_id,
                'status'            => OutreachStatusEnum::Targeting->value,
                'priority'          => $outreach->priority->value,
            ]);
            $added++;
        }

        return back()->with('success', "{$added} venue(s) carried forward to \"{$season->name}\".");
    }

    /** @return array<string, mixed> */
    private function presentSeason(BookingSeason $season): array
    {
        $statusCounts = $season->venueOutreach
            ->groupBy(fn ($o) => $o->status->value)
            ->map->count();

        return [
            'id'           => $season->id,
            'name'         => $season->name,
            'starts_on'    => $season->starts_on?->format('Y-m-d'),
            'ends_on'      => $season->ends_on?->format('Y-m-d'),
            'notes'        => $season->notes,
            'total'        => $season->venueOutreach->count(),
            'statusCounts' => $statusCounts,
        ];
    }

    /** @return array<string, mixed> */
    private function presentOutreach(VenueOutreach $outreach): array
    {
        $lastContact = $outreach->contacts->first();

        return [
            'id'           => $outreach->id,
            'venueId'      => $outreach->venue_id,
            'venueName'    => $outreach->venue->name,
            'venueCity'    => $outreach->venue->city,
            'venueState'   => $outreach->venue->state,
            'status'       => $outreach->status->value,
            'statusLabel'  => $outreach->status->label(),
            'priority'     => $outreach->priority->value,
            'priorityLabel'=> $outreach->priority->label(),
            'followUpOn'   => $outreach->follow_up_on?->format('Y-m-d'),
            'notes'        => $outreach->notes,
            'contactCount' => $outreach->contacts->count(),
            'lastContactOn'=> $lastContact?->occurred_on->format('Y-m-d'),
        ];
    }
}
