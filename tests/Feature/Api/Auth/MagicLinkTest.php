<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class MagicLinkTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // POST /api/v1/auth/magic-link
    // -------------------------------------------------------------------------

    public function test_sends_magic_link_to_known_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/magic-link', ['email' => $user->email])
            ->assertOk()
            ->assertJsonStructure(['message']);

        Notification::assertSentTo($user, MagicLinkNotification::class);
    }

    public function test_returns_200_for_unknown_email_without_sending(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/magic-link', ['email' => 'nobody@example.com'])
            ->assertOk()
            ->assertJsonStructure(['message']);

        Notification::assertNothingSent();
    }

    public function test_magic_link_request_requires_email(): void
    {
        $this->postJson('/api/v1/auth/magic-link', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_magic_link_request_rejects_invalid_email(): void
    {
        $this->postJson('/api/v1/auth/magic-link', ['email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/auth/magic-link/exchange — invite token
    // -------------------------------------------------------------------------

    public function test_exchanges_invite_token_for_bearer_token(): void
    {
        $user = User::factory()->invited()->create();

        $response = $this->postJson('/api/v1/auth/magic-link/exchange', [
            'token'       => $user->invite_token,
            'device_name' => 'iPhone 15 Pro',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'token_type',
                'user' => ['id', 'name', 'email'],
            ])
            ->assertJsonFragment(['token_type' => 'Bearer']);
    }

    public function test_invite_token_exchange_clears_the_token(): void
    {
        $user = User::factory()->invited()->create();

        $this->postJson('/api/v1/auth/magic-link/exchange', [
            'token'       => $user->invite_token,
            'device_name' => 'Pixel 8',
        ])->assertOk();

        $this->assertNull($user->fresh()->invite_token);
    }

    public function test_invite_token_exchange_updates_name_when_provided(): void
    {
        $user = User::factory()->invited()->create();

        $this->postJson('/api/v1/auth/magic-link/exchange', [
            'token'       => $user->invite_token,
            'device_name' => 'Pixel 8',
            'name'        => 'Jordan Reyes',
        ])->assertOk();

        $this->assertSame('Jordan Reyes', $user->fresh()->name);
    }

    public function test_invite_token_exchange_keeps_existing_name_when_omitted(): void
    {
        $user = User::factory()->invited()->create(['name' => 'Original Name']);

        $this->postJson('/api/v1/auth/magic-link/exchange', [
            'token'       => $user->invite_token,
            'device_name' => 'Pixel 8',
        ])->assertOk();

        $this->assertSame('Original Name', $user->fresh()->name);
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/auth/magic-link/exchange — magic link token
    // -------------------------------------------------------------------------

    public function test_exchanges_valid_magic_link_token_for_bearer_token(): void
    {
        $user = User::factory()->create();
        $user->generateMagicToken();

        $response = $this->postJson('/api/v1/auth/magic-link/exchange', [
            'token'       => $user->magic_link_token,
            'device_name' => 'iPhone 15 Pro',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'user']);
    }

    public function test_magic_link_token_is_consumed_after_exchange(): void
    {
        $user = User::factory()->create();
        $user->generateMagicToken();
        $token = $user->magic_link_token;

        $this->postJson('/api/v1/auth/magic-link/exchange', [
            'token'       => $token,
            'device_name' => 'iPhone 15 Pro',
        ])->assertOk();

        $this->assertNull($user->fresh()->magic_link_token);
    }

    public function test_expired_magic_link_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $user->generateMagicToken();

        // Backdate the expiry so the token is stale.
        $user->forceFill(['magic_link_expires_at' => now()->subMinute()])->save();

        $this->postJson('/api/v1/auth/magic-link/exchange', [
            'token'       => $user->magic_link_token,
            'device_name' => 'iPhone 15 Pro',
        ])->assertUnauthorized();
    }

    public function test_unknown_token_is_rejected(): void
    {
        $this->postJson('/api/v1/auth/magic-link/exchange', [
            'token'       => Str::random(64),
            'device_name' => 'iPhone 15 Pro',
        ])->assertUnauthorized();
    }

    public function test_exchange_requires_token_and_device_name(): void
    {
        $this->postJson('/api/v1/auth/magic-link/exchange', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['token', 'device_name']);
    }
}
