<?php

namespace App\Http\Requests\Venues;

/**
 * Editing a venue validates exactly like adding one — same fields, same rules.
 * It exists as its own type so the controller signatures read clearly and the
 * two flows can diverge later without a churny rename.
 */
class UpdateVenueRequest extends StoreVenueRequest
{
}
