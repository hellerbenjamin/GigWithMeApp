<?php

namespace Database\Factories;

use App\Models\Band;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Band>
 */
class BandFactory extends Factory
{
    protected $model = Band::class;

    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'description' => fake()->optional()->sentence(),
            'hometown' => fake()->city(),
            'founded_year' => fake()->numberBetween(1970, (int) date('Y')),
            'default_currency' => 'USD',
            'email' => fake()->optional()->safeEmail(),
            'website' => fake()->optional()->url(),
            'links' => [
                'spotify' => 'https://open.spotify.com/artist/'.fake()->bothify('??????????'),
                'instagram' => 'https://instagram.com/'.fake()->userName(),
            ],
        ];
    }
}
