<?php

namespace App\Services;

use App\Enums\GigBookingModeEnum;
use App\Enums\GigStatusEnum;
use App\Models\Gig;
use App\Notifications\GigConfirmed;
use Illuminate\Support\Facades\Notification;

/**
 * The gig booking lifecycle — how a gig moves toward "confirmed". Kept separate
 * from {@see GigService} (plain CRUD/queries) so the polling logic added in
 * later phases has a home. Controllers stay thin wrappers over this. See
 * docs/gig-booking-flow.md.
 */
class GigBookingService
{
    /**
     * Apply the gig's booking mode immediately after creation.
     *
     * - Auto: confirm it now and notify the band.
     * - Poll: leave it pending. Phase 2 will seed per-member responses and open
     *   the poll here; until then a poll gig simply sits on the calendar as
     *   pending, the same as before.
     */
    public function applyMode(Gig $gig): Gig
    {
        return match ($gig->booking_mode) {
            GigBookingModeEnum::Auto => $this->confirm($gig),
            default => $gig,
        };
    }

    /**
     * Mark a gig confirmed and SMS the band. Used by auto mode now, and by poll
     * success / admin override in Phase 2. Members without a phone number can't
     * receive the Twilio SMS, so they're skipped rather than failing the send.
     */
    public function confirm(Gig $gig): Gig
    {
        if ($gig->status !== GigStatusEnum::Confirmed) {
            $gig->update(['status' => GigStatusEnum::Confirmed->value]);
        }

        $members = $gig->band->users()->whereNotNull('phone_number')->get();
        Notification::send($members, new GigConfirmed($gig));

        return $gig;
    }
}
