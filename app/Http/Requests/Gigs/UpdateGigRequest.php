<?php

namespace App\Http\Requests\Gigs;

/**
 * Editing a gig validates exactly like booking one — same fields, same
 * band-scoped venue rule (the venue check keys off the active band, which
 * doesn't change between create and edit). It exists as its own type so the
 * controller signatures read clearly and the two flows can diverge later
 * without a churny rename.
 */
class UpdateGigRequest extends StoreGigRequest
{
}
