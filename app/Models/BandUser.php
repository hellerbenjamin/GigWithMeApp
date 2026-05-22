<?php

namespace App\Models;

use App\Enums\BandUserRoleEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['band_id', 'user_id', 'role'])]
class BandUser extends Model
{
    use HasFactory;

    protected $table = 'band_user';

    protected function casts(): array
    {
        return [
            'role' => BandUserRoleEnum::class,
        ];
    }

    public function band(): BelongsTo
    {
        return $this->belongsTo(Band::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
