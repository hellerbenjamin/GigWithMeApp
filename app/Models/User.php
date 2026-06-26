<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\HasPushSubscriptions;

#[Fillable(['name', 'email', 'phone_number', 'avatar_path', 'timezone', 'password'])]
#[Hidden(['password', 'remember_token', 'push_token', 'magic_link_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPushSubscriptions, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_active_band_id' => 'integer',
            'sms_consent_at' => 'datetime',
            'sms_opted_out_at' => 'datetime',
            'magic_link_expires_at' => 'datetime',
            'reminder_channels' => 'array',
            'reminder_days' => 'array',
        ];
    }

    /**
     * The bands this user belongs to, with their role on the pivot.
     */
    public function bands(): BelongsToMany
    {
        return $this->belongsToMany(Band::class, 'band_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Route SMS notifications to the user's phone number.
     *
     * Vonage expects E.164 (e.g. +15551234567); numbers should be stored that
     * way, or normalized before send.
     */
    public function routeNotificationForVonage(): ?string
    {
        return $this->phone_number;
    }

    /**
     * Whether we may text this member: they opted in themselves (sms_consent_at)
     * and haven't since opted out via STOP (sms_opted_out_at). Gates the Vonage
     * channel so adding a member never starts SMS on its own — only the member's
     * own opt-in does. See docs/sms-consent-invite-flow.md.
     */
    public function hasSmsConsent(): bool
    {
        return $this->sms_consent_at !== null && $this->sms_opted_out_at === null;
    }

    /**
     * The member's pending-invite token, minted when a brand-new account is
     * added to a band. It's what the emailed acceptance link carries so the
     * member can confirm their own number and opt in without a login; cleared
     * once they accept.
     */
    public function ensureInviteToken(): string
    {
        if (! $this->invite_token) {
            $this->forceFill(['invite_token' => Str::random(64)])->save();
        }

        return $this->invite_token;
    }

    /**
     * The member's durable push token, minted on first use. This is the standing
     * identity behind login-free push opt-in: it lets the RSVP page and an
     * installed PWA tie a browser's push subscription back to this user without
     * a session. See [[the push_token migration]] for why it's separate from the
     * per-gig RSVP token.
     */
    public function ensurePushToken(): string
    {
        if (! $this->push_token) {
            $this->forceFill(['push_token' => Str::random(64)])->save();
        }

        return $this->push_token;
    }

    /**
     * True when this member can receive web push — i.e. they've granted
     * permission on at least one browser. Drives channel selection so push
     * supplants SMS for subscribed members.
     */
    public function hasPushSubscription(): bool
    {
        return $this->pushSubscriptions()->exists();
    }

    /**
     * Mint a one-time magic link token valid for 15 minutes. Replaces any
     * existing token so only the most recent link works.
     */
    public function generateMagicToken(): string
    {
        $token = Str::random(64);

        $this->forceFill([
            'magic_link_token' => $token,
            'magic_link_expires_at' => now()->addMinutes(15),
        ])->save();

        return $token;
    }

    /**
     * Whether the given token matches and hasn't expired.
     */
    public function isValidMagicToken(string $token): bool
    {
        return $this->magic_link_token === $token
            && $this->magic_link_expires_at !== null
            && $this->magic_link_expires_at->isFuture();
    }

    /**
     * Consume the token after use so it can't be replayed.
     */
    public function consumeMagicToken(): void
    {
        $this->forceFill([
            'magic_link_token' => null,
            'magic_link_expires_at' => null,
        ])->save();
    }
}
