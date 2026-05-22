<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'band_id',
    'name',
    'address',
    'city',
    'state',
    'country',
    'postal_code',
    'phone',
    'email',
    'website',
    'contact_person',
    'contact_email',
    'contact_phone',
    'notes',
])]
class Venue extends Model
{
    use HasFactory;

    public function band(): BelongsTo
    {
        return $this->belongsTo(Band::class);
    }

    public function gigs(): HasMany
    {
        return $this->hasMany(Gig::class);
    }
}
