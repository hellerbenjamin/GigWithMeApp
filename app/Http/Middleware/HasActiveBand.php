<?php

namespace App\Http\Middleware;

use App\Services\BandSessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards band-scoped screens: every request through here is guaranteed to have
 * an active band by the time it reaches the controller.
 *
 * - User has bands but none active → auto-select their first.
 * - User has no bands at all → send them to band creation to onboard.
 *
 * Runs after `auth`, so a user is always present.
 */
class HasActiveBand
{
    public function __construct(private readonly BandSessionService $session) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->session->ensureActive($request->user())) {
            // No bands yet. Bounce to creation unless we're already there, which
            // would otherwise loop once that screen lands behind this middleware.
            if (! $request->routeIs('bands.create')) {
                return redirect()->route('bands.create');
            }
        }

        return $next($request);
    }
}
