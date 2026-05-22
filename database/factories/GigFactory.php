<?php

namespace Database\Factories;

use App\Enums\GigStatusEnum;
use App\Enums\GigTypeEnum;
use App\Models\Band;
use App\Models\Gig;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gig>
 */
class GigFactory extends Factory
{
    protected $model = Gig::class;

    public function definition(): array
    {
        $doors = fake()->dateTimeBetween('18:00', '20:00');
        $start = (clone $doors)->modify('+1 hour');
        $end = (clone $start)->modify('+'.fake()->numberBetween(2, 4).' hours');

        return [
            'band_id' => Band::factory(),
            // Keep the venue on the same band as the gig.
            'venue_id' => fn (array $attributes) => Venue::factory()
                ->create(['band_id' => $attributes['band_id']])
                ->id,
            'type' => GigTypeEnum::Gig,
            'status' => fake()->randomElement(GigStatusEnum::cases()),
            'name' => fake()->optional()->catchPhrase(),
            'date' => fake()->dateTimeBetween('-1 month', '+1 year')->format('Y-m-d'),
            'load_in_time' => fake()->optional()->time('H:i'),
            'soundcheck_time' => fake()->optional()->time('H:i'),
            'doors_time' => $doors->format('H:i'),
            'start_time' => $start->format('H:i'),
            'end_time' => $end->format('H:i'),
            'notes' => fake()->optional()->sentence(),
            'fee' => fake()->randomFloat(2, 100, 2000),
            'currency' => 'USD',
        ];
    }

    public function confirmed(): static
    {
        return $this->state(['status' => GigStatusEnum::Confirmed]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => GigStatusEnum::Cancelled]);
    }

    public function rehearsal(): static
    {
        return $this->state([
            'type' => GigTypeEnum::Rehearsal,
            'fee' => null,
        ]);
    }
}
