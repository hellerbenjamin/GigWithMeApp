<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug'])]
class Genre extends Model
{
    use HasFactory;

    public function bands(): BelongsToMany
    {
        return $this->belongsToMany(Band::class, 'band_genre')->withTimestamps();
    }
}
