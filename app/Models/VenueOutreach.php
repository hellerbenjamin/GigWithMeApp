<?php

namespace App\Models;

use App\Enums\OutreachPriorityEnum;
use App\Enums\OutreachStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VenueOutreach extends Model
{
    protected $table = 'venue_outreach';

    protected $fillable = [
        'booking_season_id', 'venue_id', 'status', 'priority', 'follow_up_on', 'notes',
    ];

    protected $casts = [
        'status' => OutreachStatusEnum::class,
        'priority' => OutreachPriorityEnum::class,
        'follow_up_on' => 'date',
    ];

    public function season(): BelongsTo
    {
        return $this->belongsTo(BookingSeason::class, 'booking_season_id');
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(OutreachContact::class)->orderByDesc('occurred_on');
    }
}
