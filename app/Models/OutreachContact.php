<?php

namespace App\Models;

use App\Enums\OutreachContactMethodEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutreachContact extends Model
{
    protected $fillable = [
        'venue_outreach_id', 'occurred_on', 'method', 'summary', 'response',
    ];

    protected $casts = [
        'method' => OutreachContactMethodEnum::class,
        'occurred_on' => 'date',
    ];

    public function outreach(): BelongsTo
    {
        return $this->belongsTo(VenueOutreach::class, 'venue_outreach_id');
    }
}
