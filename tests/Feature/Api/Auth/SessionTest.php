<?php

namespace Tests\Feature\Api\Auth;

use App\Models\Band;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_returns_the_current_user_and_their_bands(): void
    {
        $user = User::factory()->create();
        $band = Band::factory()->create();
        $user->bands()->attach($band, ['role' => 'member']);

        $this->withToken($user->createToken('test')->plainTextToken)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonCount(1, 'bands')
            ->assertJsonPath('bands.0.id', $band->id)
            ->assertJsonPath('bands.0.role', 'member');
    }

    public function test_me_reflects_a_band_added_after_the_token_was_issued(): void
    {
        // The exact mobile bug: signed in with no bands, added to one later.
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonCount(0, 'bands');

        $band = Band::factory()->create();
        $user->bands()->attach($band, ['role' => 'member']);

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonCount(1, 'bands')
            ->assertJsonPath('bands.0.id', $band->id);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }
}
