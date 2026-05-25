<?php

namespace App\Services;

use App\Http\Requests\Gigs\StoreGigRequest;
use App\Models\Band;
use App\Models\Gig;
use Illuminate\Database\Eloquent\Collection;

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
     * Remove a gig from the calendar. Callers must have confirmed the gig
     * belongs to the acting band (see {@see \App\Http\Controllers\Gigs\GigController::destroy}).
     */
    public function deleteGig(Gig $gig): void
    {
        $gig->delete();
    }
}
