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
     *
     * The choice is mirrored onto the user's `last_active_band_id` so it
     * survives a session reset (reseed, cleared cookies); the session stays the
     * fast per-request source of truth.
     */
    public function set(Band $band): void
    {
        session([self::SESSION_KEY => $band->getKey()]);

        $user = auth()->user();

        if ($user && $user->last_active_band_id !== $band->getKey()) {
            $user->forceFill(['last_active_band_id' => $band->getKey()])->save();
        }

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
     * Ensure the user has an active band, selecting one when the session has
     * none or points at a band they've since left. Preference order:
     *
     *   1. the band they were last working in (`last_active_band_id`), so a
     *      session reset doesn't lose their choice;
     *   2. otherwise their first band by name.
     *
     * Both are scoped to current memberships, so a since-left or deleted band
     * is skipped rather than surfaced.
     *
     * Returns false when the user belongs to no bands at all — the signal the
     * middleware uses to send them to band creation.
     */
    public function ensureActive(User $user): bool
    {
        if ($this->band()) {
            return true;
        }

        $band = null;

        if ($user->last_active_band_id) {
            $band = $user->bands()->whereKey($user->last_active_band_id)->first();
        }

        $band ??= $user->bands()->orderBy('bands.name')->first();

        if (! $band) {
            return false;
        }

        $this->set($band);

        return true;
    }
}
