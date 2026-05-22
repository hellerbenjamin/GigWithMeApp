<?php

namespace App\Services;

use App\Enums\BandUserRoleEnum;
use App\Models\Band;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Band lifecycle operations. Controllers stay thin wrappers over this; the
 * active-band session state is delegated to {@see BandSessionService}.
 */
class BandService
{
    public function __construct(private readonly BandSessionService $session) {}

    /**
     * Create a band, make its creator the owner, attach any genres, and switch
     * the creator into it. Atomic — a half-created band never reaches the user.
     *
     * @param  array<string, mixed>  $attributes  fillable band columns (no slug)
     * @param  array<int, string>  $genres  genre names; created on demand
     */
    public function createBand(User $creator, array $attributes, array $genres = []): Band
    {
        return DB::transaction(function () use ($creator, $attributes, $genres): Band {
            $band = new Band($attributes);
            $band->slug = $this->uniqueSlug($attributes['name']);
            $band->save();

            $creator->bands()->attach($band, ['role' => BandUserRoleEnum::Owner->value]);

            if ($genres !== []) {
                $band->genres()->sync($this->resolveGenreIds($genres));
            }

            $this->session->set($band);

            return $band;
        });
    }

    /**
     * A URL-safe slug for the name, suffixed (-2, -3, …) until it's unique.
     */
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'band';
        $slug = $base;
        $suffix = 2;

        while (Band::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * Map genre names to ids, creating any that don't exist yet. Matching is by
     * slug so "Dream Pop" and "dream pop" collapse to one genre.
     *
     * @param  array<int, string>  $names
     * @return array<int, int>
     */
    private function resolveGenreIds(array $names): array
    {
        return collect($names)
            ->map(static fn (string $name) => trim($name))
            ->filter()
            ->unique(static fn (string $name) => Str::slug($name))
            ->map(static fn (string $name) => Genre::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            )->id)
            ->values()
            ->all();
    }
}
