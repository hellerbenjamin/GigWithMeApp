<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingSeason extends Model
{
    protected $fillable = ['band_id', 'name', 'starts_on', 'ends_on', 'notes'];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
    ];

    public function band(): BelongsTo
    {
        return $this->belongsTo(Band::class);
    }

    public function venueOutreach(): HasMany
    {
        return $this->hasMany(VenueOutreach::class);
    }
}
