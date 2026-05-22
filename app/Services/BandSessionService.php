<?php

namespace App\Services;

use App\Models\Band;
use App\Models\User;

/**
 * Owns the "active band" — the one band a user is currently working in, stored
 * in the session under {@see self::SESSION_KEY} and resolved to a model on
 * demand. This is the backbone the BandSwitcher and every band-scoped screen
 * lean on (see docs/legacy-app-features.md §2).
 *
 * Registered as a scoped singleton, so the resolved-band cache below lives for
 * exactly one request.
 */
class BandSessionService
{
    private const SESSION_KEY = 'active_band_id';

    /** Per-request cache of the resolved active band (null = resolved to "none"). */
    private ?Band $cachedBand = null;

    private bool $resolved = false;

    /**
     * The active band's id from the session, if any.
     */
    public function id(): ?int
    {
        return session(self::SESSION_KEY);
    }

    /**
     * Make the given band active. Callers are responsible for confirming the
     * user belongs to the band first (see SetActiveBandController).
     */
    public function set(Band $band): void
    {
        session([self::SESSION_KEY => $band->getKey()]);

        $this->cachedBand = $band;
        $this->resolved = true;
    }

    /**
     * Forget the active band (e.g. on logout).
     */
    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);

        $this->cachedBand = null;
        $this->resolved = false;
    }

    /**
     * Resolve the active band to a model, scoped to the authenticated user's
     * memberships so a stale or forged session id can never surface a band the
     * user doesn't belong to. Returns null when nothing is active.
     */
    public function band(): ?Band
    {
        if ($this->resolved) {
            return $this->cachedBand;
        }

        $this->resolved = true;

        $user = auth()->user();
        $id = $this->id();

        if (! $user || ! $id) {
            return $this->cachedBand = null;
        }

        return $this->cachedBand = $user->bands()->whereKey($id)->first();
    }

    /**
     * Ensure the user has an active band, auto-selecting their first (by name)
     * when the session has none or points at a band they've since left.
     *
     * Returns false when the user belongs to no bands at all — the signal the
     * middleware uses to send them to band creation.
     */
    public function ensureActive(User $user): bool
    {
        if ($this->band()) {
            return true;
        }

        $first = $user->bands()->orderBy('bands.name')->first();

        if (! $first) {
            return false;
        }

        $this->set($first);

        return true;
    }
}
