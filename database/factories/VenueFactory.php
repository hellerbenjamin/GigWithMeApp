<?php

namespace Database\Factories;

use App\Models\Band;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Venue>
 */
class VenueFactory extends Factory
{
    protected $model = Venue::class;

    public function definition(): array
    {
        return [
            'band_id' => Band::factory(),
            'name' => fake()->company().' '.fake()->randomElement(['Hall', 'Club', 'Lounge', 'Theatre', 'Bar']),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'country' => fake()->country(),
            'postal_code' => fake()->postcode(),
            'phone' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->companyEmail(),
            'website' => fake()->optional()->url(),
            'contact_person' => fake()->optional()->name(),
            'contact_email' => fake()->optional()->safeEmail(),
            'contact_phone' => fake()->optional()->phoneNumber(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function withGigDefaults(): static
    {
        return $this->state([
            'default_load_in_time' => '16:00',
            'default_soundcheck_time' => '17:30',
            'default_doors_time' => '19:00',
            'default_start_time' => '20:00',
            'default_end_time' => '22:30',
            'default_notes' => 'Backline provided. Load in via the alley.',
        ]);
    }
}
