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
}
