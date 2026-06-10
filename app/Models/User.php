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
#[Hidden(['password', 'remember_token', 'push_token'])]
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
}
