<?php

namespace App\Models;

use App\Enums\BandUserRoleEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'genre',
    'website',
    'email',
    'facebook',
    'instagram',
    'twitter',
    'youtube',
    'spotify',
    'apple_music',
    'bandcamp',
    'soundcloud',
    'tiktok',
    'twitch',
    'patreon',
])]
class Band extends Model
{
    use HasFactory;

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'band_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function owners(): BelongsToMany
    {
        return $this->users()->wherePivot('role', BandUserRoleEnum::Owner->value);
    }

    public function admins(): BelongsToMany
    {
        return $this->users()->wherePivot('role', BandUserRoleEnum::Admin->value);
    }

    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }

    public function gigs(): HasMany
    {
        return $this->hasMany(Gig::class);
    }

    /**
     * The given user's role within this band, or null if they're not a member.
     */
    public function getUserRole(User $user): ?BandUserRoleEnum
    {
        $role = $this->users()->whereKey($user->getKey())->first()?->pivot->role;

        return $role !== null ? BandUserRoleEnum::from($role) : null;
    }
}
