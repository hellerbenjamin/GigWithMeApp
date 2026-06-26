<?php

namespace App\Services;

use App\Enums\BandUserRoleEnum;
use App\Models\Band;
use App\Models\User;
use App\Notifications\BandInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Roster operations — who belongs to a band and in what role. A member is added
 * by email: an existing account is reused, and a brand-new one is created on the
 * spot when the email is unknown. New (and not-yet-accepted) members are emailed
 * an invitation to set up their notification preferences and opt in to SMS
 * themselves — adding a member never collects a phone number or starts texting.
 * See docs/sms-consent-invite-flow.md. Controllers stay thin wrappers over this.
 */
class BandMemberService
{
    /**
     * Add a member to the band by email. If a user with that email already
     * exists they're attached as-is; otherwise a fresh account is created (with
     * a random password — they can reset it later) and then attached. Atomic:
     * a created-but-unattached user never escapes the transaction.
     *
     * A brand-new account, or an existing one that hasn't accepted a prior
     * invite yet, is emailed an invitation to the acceptance page (queued, so
     * it's dispatched after the transaction commits). Established members are
     * simply attached.
     *
     * Callers must have confirmed the email isn't already on the roster (see
     * StoreBandMemberRequest), so the pivot insert won't hit the unique key.
     *
     * @param  array{name: string, email: string, role: string}  $attributes
     * @return array{user: User, created: bool} created = a new account was made
     */
    public function addMember(Band $band, array $attributes): array
    {
        ['user' => $user, 'created' => $created] = DB::transaction(function () use ($band, $attributes): array {
            $existing = User::where('email', $attributes['email'])->first();

            $user = $existing ?? User::create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                // A throwaway password keeps the NOT NULL column happy; the new
                // member sets their own via password reset on first sign-in.
                'password' => Str::password(),
            ]);

            $user->ensureInviteToken();

            $band->users()->attach($user, [
                'role' => BandUserRoleEnum::from($attributes['role'])->value,
                'critical' => (bool) ($attributes['critical'] ?? true),
            ]);

            return ['user' => $user, 'created' => $existing === null];
        });

        $user->notify(new BandInvitation($band));

        return ['user' => $user, 'created' => $created];
    }

    /**
     * Update a roster member: their shared account profile (name / email) plus
     * their role within this band. Unlike adding, this is an explicit edit by a
     * manager, so it does overwrite the user's own account fields. The phone
     * number is deliberately not editable here — it's member-owned and only set
     * via the opt-in flow. Atomic so a half-applied edit never escapes.
     *
     * @param  array{name: string, email: string, role: string}  $attributes
     */
    public function updateMember(Band $band, User $user, array $attributes): void
    {
        DB::transaction(function () use ($band, $user, $attributes): void {
            $user->update([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
            ]);

            $band->users()->updateExistingPivot($user->getKey(), [
                'role' => BandUserRoleEnum::from($attributes['role'])->value,
                'critical' => (bool) ($attributes['critical'] ?? true),
            ]);
        });
    }

    /**
     * Remove a member from the band's roster. Detaching only severs the pivot
     * row — the user's account itself is left intact (they may belong to other
     * bands). A no-op if the user isn't on this roster.
     */
    public function removeMember(Band $band, User $user): void
    {
        $band->users()->detach($user->getKey());
    }

    /**
     * Whether changing this user to the given role would leave the band with no
     * owner — i.e. demoting its last remaining owner. Blocks the role edit that
     * would orphan the band, the same way removal does.
     */
    public function wouldDemoteLastOwner(Band $band, User $user, BandUserRoleEnum $newRole): bool
    {
        if ($newRole === BandUserRoleEnum::Owner) {
            return false;
        }

        if ($band->getUserRole($user) !== BandUserRoleEnum::Owner) {
            return false;
        }

        return $band->owners()->count() <= 1;
    }

    /**
     * Whether removing this user would leave the band with no owner. Used to
     * block the removal that would orphan the band — every band keeps at least
     * one owner who can manage it.
     */
    public function wouldLeaveBandOwnerless(Band $band, User $user): bool
    {
        if ($band->getUserRole($user) !== BandUserRoleEnum::Owner) {
            return false;
        }

        return $band->owners()->count() <= 1;
    }
}
