<?php

namespace App\Models;

use App\Enums\GigStatusEnum;
use App\Enums\GigTypeEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'band_id',
    'venue_id',
    'type',
    'status',
    'name',
    'date',
    'load_in_time',
    'soundcheck_time',
    'doors_time',
    'start_time',
    'end_time',
    'notes',
    'fee',
    'currency',
])]
class Gig extends Model
{
    use HasFactory;

    /**
     * In-memory defaults mirroring the DB-level column defaults, so a freshly
     * created Gig has a populated status/type/currency before it's reloaded.
     */
    protected $attributes = [
        'type' => 'gig',
        'status' => 'pending',
        'currency' => 'USD',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'fee' => 'decimal:2',
            'type' => GigTypeEnum::class,
            'status' => GigStatusEnum::class,
        ];
    }

    public function band(): BelongsTo
    {
        return $this->belongsTo(Band::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
