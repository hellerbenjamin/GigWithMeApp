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
     * Mark a gig confirmed and SMS the band. Used by auto mode, by a poll where
     * everyone's available, and by an admin's manual confirm. Members without a
     * phone number can't get the Twilio SMS, so they're skipped rather than
     * failing the send.
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

    /**
     * Open the poll: seed a reply row for every current member (the creator is
     * auto-marked available), then ask everyone still pending who can receive an
     * SMS. A solo band is already fully available, so evaluatePoll confirms it.
     */
    public function openPoll(Gig $gig, User $actor): Gig
    {
        foreach ($gig->band->users()->get() as $member) {
            $isActor = $member->is($actor);

            $gig->memberResponses()->create([
                'user_id' => $member->getKey(),
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
            ->filter(fn (GigMemberResponse $r) => $r->status === GigResponseStatusEnum::Pending
                && filled($r->user->phone_number))
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
     * - everyone available → confirm;
     * - everyone replied but some can't → notify admins, exactly once.
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

        $allAvailable = $responses->every(
            fn (GigMemberResponse $r) => $r->status === GigResponseStatusEnum::Available,
        );

        if ($allAvailable) {
            $this->confirm($gig);

            return;
        }

        // Everyone replied, someone can't make it — hand it to the admins, but
        // stamp poll_closed_at so a later answer-change doesn't re-notify them.
        if ($gig->poll_closed_at === null) {
            $gig->poll_closed_at = now();
            $gig->save();

            $this->notifyAdmins($gig);
        }
    }

    /**
     * SMS the band's owners and admins that a poll needs their decision.
     */
    private function notifyAdmins(Gig $gig): void
    {
        $recipients = $gig->band->users()
            ->wherePivotIn('role', [BandUserRoleEnum::Owner->value, BandUserRoleEnum::Admin->value])
            ->whereNotNull('phone_number')
            ->get();

        Notification::send($recipients, new GigPollNeedsAttention($gig));
    }
}
