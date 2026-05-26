<?php

namespace App\Services;

use App\Enums\GigStatusEnum;
use App\Http\Controllers\Gigs\GigController;
use App\Http\Requests\Gigs\StoreGigRequest;
use App\Http\Requests\Gigs\UpdateGigRequest;
use App\Models\Band;
use App\Models\Gig;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Gig lifecycle + queries for a band. Controllers stay thin wrappers over this
 * (see the architecture decision in docs/legacy-app-features.md §5).
 */
class GigService
{
    /**
     * The band's gigs, venue eager-loaded, ordered by date (soonest first).
     *
     * @return Collection<int, Gig>
     */
    public function getBandGigs(Band $band): Collection
    {
        return $band->gigs()
            ->with('venue:id,name')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * The band's soonest upcoming gigs for the dashboard — today onward, dropping
     * cancelled ones, venue eager-loaded, nearest first.
     *
     * @return Collection<int, Gig>
     */
    public function getUpcomingGigs(Band $band, int $limit = 5): Collection
    {
        return $this->upcoming($band)
            ->with('venue:id,name')
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit($limit)
            ->get();
    }

    /**
     * How many gigs are still ahead of the band — today onward, cancelled excluded.
     * Mirrors {@see self::getUpcomingGigs()} so the stat and the list agree.
     */
    public function countUpcoming(Band $band): int
    {
        return $this->upcoming($band)->count();
    }

    /**
     * Total confirmed fees for gigs dated within the current calendar month —
     * the band's money on the books right now.
     */
    public function bookedThisMonth(Band $band): float
    {
        return (float) $band->gigs()
            ->where('status', GigStatusEnum::Confirmed->value)
            ->whereBetween('date', [
                Carbon::now()->startOfMonth()->toDateString(),
                Carbon::now()->endOfMonth()->toDateString(),
            ])
            ->sum('fee');
    }

    /**
     * Shared base query for "upcoming" gigs: today onward, cancelled excluded.
     *
     * @return HasMany<Gig, Band>
     */
    private function upcoming(Band $band): HasMany
    {
        return $band->gigs()
            ->whereDate('date', '>=', Carbon::today()->toDateString())
            ->where('status', '!=', GigStatusEnum::Cancelled->value);
    }

    /**
     * Create a gig for the band. The venue (when given) is already confirmed to
     * belong to the band by {@see StoreGigRequest}, so
     * the attributes can be persisted straight through the relationship — which
     * also stamps band_id for us.
     *
     * @param  array<string, mixed>  $attributes  validated, fillable gig columns
     */
    public function createGig(Band $band, array $attributes): Gig
    {
        return $band->gigs()->create($attributes);
    }

    /**
     * Update an existing gig. The venue (when given) is already confirmed to
     * belong to the band by {@see UpdateGigRequest}, and
     * band_id isn't in the validated set, so the gig can never be moved to
     * another band. Callers must have confirmed the gig belongs to the acting
     * band (see {@see GigController::edit}).
     *
     * @param  array<string, mixed>  $attributes  validated, fillable gig columns
     */
    public function updateGig(Gig $gig, array $attributes): Gig
    {
        $gig->update($attributes);

        return $gig;
    }

    /**
     * Remove a gig from the calendar. Callers must have confirmed the gig
     * belongs to the acting band (see {@see GigController::destroy}).
     */
    public function deleteGig(Gig $gig): void
    {
        $gig->delete();
    }
}
