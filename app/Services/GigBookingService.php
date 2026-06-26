<?php

namespace App\Services;

use App\Enums\BandUserRoleEnum;
use App\Enums\GigBookingModeEnum;
use App\Enums\GigResponseStatusEnum;
use App\Enums\GigStatusEnum;
use App\Models\Gig;
use App\Models\GigMemberResponse;
use App\Models\User;
use App\Notifications\GigConfirmed;
use App\Notifications\GigPollNeedsAttention;
use App\Notifications\GigPollOpened;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * The gig booking lifecycle — how a gig moves toward "confirmed". Kept separate
 * from {@see GigService} (plain CRUD/queries). Every transport that records a
 * member's reply (web magic link now; SMS / RCS later) funnels through
 * {@see self::recordResponse()}. See docs/gig-booking-flow.md.
 */
class GigBookingService
{
    /**
     * Apply the gig's booking mode immediately after creation.
     *
     * - Auto: confirm it now and notify the band.
     * - Poll: open the poll — seed a reply row per member and ask each one.
     *
     * @param  User  $actor  the member creating the gig; auto-marked available
     */
    public function applyMode(Gig $gig, User $actor): Gig
    {
        return match ($gig->booking_mode) {
            GigBookingModeEnum::Auto => $this->confirm($gig),
            GigBookingModeEnum::Poll => $this->openPoll($gig, $actor),
        };
    }

    /**
     * Mark a gig confirmed and notify the band by SMS and/or email. Used by auto
     * mode, by a poll where everyone's available, and by an admin's manual
     * confirm. Each member is reached on whichever channels they have (phone for
     * SMS, address for email); the notification skips anyone with neither.
     */
    public function confirm(Gig $gig): Gig
    {
        if ($gig->status !== GigStatusEnum::Confirmed) {
            $gig->update(['status' => GigStatusEnum::Confirmed->value]);
        }

        Notification::send($gig->band->users()->get(), new GigConfirmed($gig));

        return $gig;
    }

    /**
     * Open the poll: seed a reply row for every current member (the creator is
     * auto-marked available), then ask everyone still pending — by SMS and/or
     * email. A solo band is already fully available, so evaluatePoll confirms it.
     */
    public function openPoll(Gig $gig, User $actor): Gig
    {
        foreach ($gig->band->users()->withPivot('critical')->get() as $member) {
            $isActor = $member->is($actor);

            $gig->memberResponses()->create([
                'user_id' => $member->getKey(),
                'critical' => (bool) $member->pivot->critical,
                'status' => $isActor
                    ? GigResponseStatusEnum::Available->value
                    : GigResponseStatusEnum::Pending->value,
                'responded_at' => $isActor ? now() : null,
                'channel' => $isActor ? 'web' : null,
                'token' => Str::random(40),
            ]);
        }

        $gig->load('memberResponses.user');

        $gig->memberResponses
            ->filter(fn (GigMemberResponse $r) => $r->status === GigResponseStatusEnum::Pending)
            ->each(fn (GigMemberResponse $r) => $r->user->notify(new GigPollOpened($r)));

        $this->evaluatePoll($gig);

        return $gig;
    }

    /**
     * Re-run a poll from scratch. Used when the gig's date changed, or when an
     * admin reopens a poll that closed needing attention: every reply is wiped
     * back to pending (a stale "yes" to the old plan shouldn't carry over), the
     * needs-attention stamp is cleared, and everyone is asked again by SMS and/or
     * email. The actor is auto-marked available, exactly as on the first open.
     *
     * @param  User  $actor  the member re-polling; auto-marked available
     */
    public function rePoll(Gig $gig, User $actor): Gig
    {
        // Reopen the gig: a confirmed/cancelled poll becomes pending again so
        // evaluatePoll can settle it afresh, and the attention stamp is cleared.
        // poll_closed_at isn't fillable (guarded like in evaluatePoll), so it's
        // set directly rather than mass-assigned.
        $gig->status = GigStatusEnum::Pending->value;
        $gig->poll_closed_at = null;
        $gig->save();

        $criticalByUser = $gig->band->users()
            ->withPivot('critical')
            ->get()
            ->keyBy('id')
            ->map(fn (User $u) => (bool) $u->pivot->critical);

        foreach ($gig->memberResponses()->get() as $response) {
            $isActor = $response->user_id === $actor->getKey();

            $response->update([
                'critical' => $criticalByUser[$response->user_id] ?? true,
                'status' => $isActor
                    ? GigResponseStatusEnum::Available->value
                    : GigResponseStatusEnum::Pending->value,
                'responded_at' => $isActor ? now() : null,
                'channel' => $isActor ? 'web' : null,
                'note' => null,
            ]);
        }

        $gig->load('memberResponses.user');

        $gig->memberResponses
            ->filter(fn (GigMemberResponse $r) => $r->status === GigResponseStatusEnum::Pending)
            ->each(fn (GigMemberResponse $r) => $r->user->notify(new GigPollOpened($r)));

        $this->evaluatePoll($gig);

        return $gig;
    }

    /**
     * Record one member's reply, from whatever transport (web / sms / rcs), then
     * re-check whether the poll is settled. The single funnel all channels share.
     */
    public function recordResponse(
        GigMemberResponse $response,
        GigResponseStatusEnum $status,
        string $channel,
        ?string $note = null,
    ): GigMemberResponse {
        $response->update([
            'status' => $status->value,
            'responded_at' => now(),
            'channel' => $channel,
            'note' => $note,
        ]);

        $this->evaluatePoll($response->gig);

        return $response;
    }

    /**
     * Decide the poll's fate once a reply lands:
     * - still someone pending → keep waiting;
     * - all critical members available → confirm (non-critical unavailables don't block);
     * - everyone replied but a critical member can't → notify admins, exactly once.
     *
     * If no members are marked critical, falls back to requiring everyone available.
     */
    public function evaluatePoll(Gig $gig): void
    {
        // Only an open (pending) poll can be settled here. Confirming is
        // terminal; a closed-for-attention poll is the admins' call now.
        if ($gig->status !== GigStatusEnum::Pending) {
            return;
        }

        $responses = $gig->memberResponses()->get();

        if ($responses->isEmpty()
            || $responses->contains(fn (GigMemberResponse $r) => $r->status === GigResponseStatusEnum::Pending)) {
            return;
        }

        $criticalResponses = $responses->where('critical', true);

        // With no critical members defined, require all members to be available.
        $threshold = $criticalResponses->isEmpty() ? $responses : $criticalResponses;

        $allThresholdAvailable = $threshold->every(
            fn (GigMemberResponse $r) => $r->status === GigResponseStatusEnum::Available,
        );

        if ($allThresholdAvailable) {
            $this->confirm($gig);

            return;
        }

        // Everyone replied, a critical member can't make it — hand it to the admins,
        // stamping poll_closed_at so a later answer-change doesn't re-notify them.
        if ($gig->poll_closed_at === null) {
            $gig->poll_closed_at = now();
            $gig->save();

            $this->notifyAdmins($gig);
        }
    }

    /**
     * Notify the band's owners and admins, by SMS and/or email, that a poll
     * needs their decision.
     */
    private function notifyAdmins(Gig $gig): void
    {
        $recipients = $gig->band->users()
            ->wherePivotIn('role', [BandUserRoleEnum::Owner->value, BandUserRoleEnum::Admin->value])
            ->get();

        Notification::send($recipients, new GigPollNeedsAttention($gig));
    }
}
