<?php

namespace Database\Factories;

use App\Enums\GigResponseStatusEnum;
use App\Models\Gig;
use App\Models\GigMemberResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GigMemberResponse>
 */
class GigMemberResponseFactory extends Factory
{
    protected $model = GigMemberResponse::class;

    public function definition(): array
    {
        return [
            'gig_id'       => Gig::factory(),
            'user_id'      => User::factory(),
            'status'       => GigResponseStatusEnum::Pending,
            'critical'     => true,
            'responded_at' => null,
            'channel'      => null,
            'note'         => null,
            'token'        => Str::random(64),
        ];
    }

    public function available(): static
    {
        return $this->state([
            'status'       => GigResponseStatusEnum::Available,
            'responded_at' => now(),
            'channel'      => 'web',
        ]);
    }

    public function unavailable(): static
    {
        return $this->state([
            'status'       => GigResponseStatusEnum::Unavailable,
            'responded_at' => now(),
            'channel'      => 'web',
        ]);
    }
}
