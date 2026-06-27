<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_bearer_token_on_valid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/auth/login', [
            'email'       => $user->email,
            'password'    => 'password',
            'device_name' => 'Pixel 8',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'token_type',
                'user' => ['id', 'name', 'email'],
            ])
            ->assertJsonFragment([
                'token_type' => 'Bearer',
                'email'      => $user->email,
            ]);
    }

    public function test_login_rejects_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/login', [
            'email'       => $user->email,
            'password'    => 'wrong-password',
            'device_name' => 'Pixel 8',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_login_rejects_unknown_email(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email'       => 'nobody@example.com',
            'password'    => 'password',
            'device_name' => 'Pixel 8',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_login_requires_all_fields(): void
    {
        $this->postJson('/api/v1/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password', 'device_name']);
    }
}
