<?php

namespace App\Facades;

use App\Models\Band;
use App\Models\User;
use App\Services\BandSessionService;
use Illuminate\Support\Facades\Facade;

/**
 * App-wide access to the current active band.
 *
 * The accessor resolves the {@see BandSessionService} from the container
 * directly — there is no separate singleton bound to the facade name (the
 * legacy app did that and it was redundant; see legacy bug #9).
 *
 * @method static int|null id()
 * @method static void set(Band $band)
 * @method static void clear()
 * @method static Band|null band()
 * @method static bool ensureActive(User $user)
 *
 * @see BandSessionService
 */
class ActiveBand extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BandSessionService::class;
    }
}
