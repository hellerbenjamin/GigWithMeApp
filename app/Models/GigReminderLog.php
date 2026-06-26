<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['gig_id', 'user_id', 'days_before', 'sent_at'])]
class GigReminderLog extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'days_before' => 'integer',
        ];
    }

    public function gig(): BelongsTo
    {
        return $this->belongsTo(Gig::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
