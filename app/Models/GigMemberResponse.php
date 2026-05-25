<?php

namespace App\Models;

use App\Enums\GigResponseStatusEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One band member's reply to a poll-mode gig. Seeded (status `pending`) for
 * every member when the poll opens; the unguessable `token` is what the magic
 * link — and later an RCS button — carries to record a reply without a login.
 */
#[Fillable([
    'gig_id',
    'user_id',
    'status',
    'responded_at',
    'channel',
    'note',
    'token',
])]
class GigMemberResponse extends Model
{
    use HasFactory;

    protected $table = 'gig_member_responses';

    protected function casts(): array
    {
        return [
            'status' => GigResponseStatusEnum::class,
            'responded_at' => 'datetime',
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
