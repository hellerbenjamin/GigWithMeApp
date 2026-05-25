<?php

namespace App\Services;

use App\Models\Band;
use App\Models\Venue;

/**
 * Venue lifecycle operations. Venues are private to a single band (see the
 * "venue-scoping-intentional" decision), so every venue is created through its
 * owning band rather than by a free-floating band_id. Controllers stay thin
 * wrappers over this.
 */
class VenueService
{
    /**
     * Create a venue belonging to the given band.
     *
     * @param  array<string, mixed>  $attributes  fillable venue columns (no band_id)
     */
    public function createVenue(Band $band, array $attributes): Venue
    {
        return $band->venues()->create($attributes);
    }

    /**
     * Update an existing venue. band_id isn't in the validated set, so a venue
     * can never be moved to another band. Callers must have confirmed the venue
     * belongs to the acting band (see {@see \App\Http\Controllers\VenueController::edit}).
     *
     * @param  array<string, mixed>  $attributes  validated, fillable venue columns
     */
    public function updateVenue(Venue $venue, array $attributes): Venue
    {
        $venue->update($attributes);

        return $venue;
    }

    /**
     * Remove a venue. Gigs that referenced it keep their history — the gigs
     * table's venue_id is nullOnDelete, so those gigs simply revert to a TBD
     * venue. Callers must have confirmed the venue belongs to the acting band
     * (see {@see \App\Http\Controllers\VenueController::destroy}).
     */
    public function deleteVenue(Venue $venue): void
    {
        $venue->delete();
    }
}
