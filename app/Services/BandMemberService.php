<?php

namespace App\Services;

use App\Enums\BandUserRoleEnum;
use App\Models\Band;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Roster operations — who belongs to a band and in what role. There's no invite
 * / email-sending flow yet, so a member is added by email: an existing account
 * is reused, and a brand-new one is created on the spot when the email is
 * unknown. Controllers stay thin wrappers over this.
 */
class BandMemberService
{
    /**
     * Add a member to the band by email. If a user with that email already
     * exists they're attached as-is; otherwise a fresh account is created (with
     * a random password — they can reset it later) and then attached. Atomic:
     * a created-but-unattached user never escapes the transaction.
     *
     * Callers must have confirmed the email isn't already on the roster (see
     * StoreBandMemberRequest), so the pivot insert won't hit the unique key.
     *
     * @param  array{name: string, email: string, role: string}  $attributes
     * @return array{user: User, created: bool}  created = a new account was made
     */
    public function addMember(Band $band, array $attributes): array
    {
        return DB::transaction(function () use ($band, $attributes): array {
            $existing = User::where('email', $attributes['email'])->first();

            $user = $existing ?? User::create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                // A throwaway password keeps the NOT NULL column happy; the new
                // member sets their own via password reset on first sign-in.
                'password' => Str::password(),
            ]);

            $band->users()->attach($user, [
                'role' => BandUserRoleEnum::from($attributes['role'])->value,
            ]);

            return ['user' => $user, 'created' => $existing === null];
        });
    }
}
