<?php

namespace Tests\Feature\Api\Member;

use App\Models\MobilePushToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // POST /api/v1/notifications/push-token
    // -------------------------------------------------------------------------

    public function test_register_token_creates_record(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/notifications/push-token', [
                'token'       => 'ExponentPushToken[abc123]',
                'device_name' => 'Pixel 7',
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('mobile_push_tokens', [
            'user_id'     => $user->id,
            'token'       => 'ExponentPushToken[abc123]',
            'device_name' => 'Pixel 7',
        ]);
    }

    public function test_register_token_upserts_on_duplicate(): void
    {
        $user = User::factory()->create();

        MobilePushToken::create([
            'user_id'     => $user->id,
            'token'       => 'ExponentPushToken[abc123]',
            'device_name' => 'Old Name',
        ]);

        $this->withToken($user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/notifications/push-token', [
                'token'       => 'ExponentPushToken[abc123]',
                'device_name' => 'New Name',
            ])
            ->assertOk();

        $this->assertSame(1, MobilePushToken::count());
        $this->assertDatabaseHas('mobile_push_tokens', ['device_name' => 'New Name']);
    }

    public function test_register_token_rejects_non_expo_token(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/notifications/push-token', ['token' => 'not-an-expo-token'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');
    }

    public function test_register_token_requires_authentication(): void
    {
        $this->postJson('/api/v1/notifications/push-token', ['token' => 'ExponentPushToken[abc]'])
            ->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/notifications/push-token
    // -------------------------------------------------------------------------

    public function test_remove_token_deletes_record(): void
    {
        $user = User::factory()->create();

        MobilePushToken::create([
            'user_id' => $user->id,
            'token'   => 'ExponentPushToken[abc123]',
        ]);

        $this->withToken($user->createToken('test')->plainTextToken)
            ->deleteJson('/api/v1/notifications/push-token', [
                'token' => 'ExponentPushToken[abc123]',
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseMissing('mobile_push_tokens', ['token' => 'ExponentPushToken[abc123]']);
    }

    public function test_remove_token_cannot_delete_another_users_token(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        MobilePushToken::create([
            'user_id' => $userB->id,
            'token'   => 'ExponentPushToken[other]',
        ]);

        $this->withToken($userA->createToken('test')->plainTextToken)
            ->deleteJson('/api/v1/notifications/push-token', [
                'token' => 'ExponentPushToken[other]',
            ])
            ->assertOk();

        // Token still exists because it belongs to user B.
        $this->assertDatabaseHas('mobile_push_tokens', ['token' => 'ExponentPushToken[other]']);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/notifications/preferences
    // -------------------------------------------------------------------------

    public function test_show_preferences_returns_defaults(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->getJson('/api/v1/notifications/preferences')
            ->assertOk()
            ->assertJsonPath('data.channels', ['email'])
            ->assertJsonPath('data.days', [7, 1])
            ->assertJsonStructure(['data' => ['channels', 'days', 'available_days']]);
    }

    public function test_show_preferences_requires_authentication(): void
    {
        $this->getJson('/api/v1/notifications/preferences')->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // PUT /api/v1/notifications/preferences
    // -------------------------------------------------------------------------

    public function test_update_preferences_saves_channels_and_days(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->putJson('/api/v1/notifications/preferences', [
                'channels' => ['mobile', 'email'],
                'days'     => [7, 0],
            ])
            ->assertOk()
            ->assertJsonPath('data.channels', ['mobile', 'email'])
            ->assertJsonPath('data.days', [7, 0]);

        $this->assertSame(['mobile', 'email'], $user->fresh()->reminder_channels);
        $this->assertSame([7, 0], $user->fresh()->reminder_days);
    }

    public function test_update_preferences_rejects_invalid_channel(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->putJson('/api/v1/notifications/preferences', [
                'channels' => ['carrier-pigeon'],
                'days'     => [1],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('channels.0');
    }

    public function test_update_preferences_rejects_invalid_day(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->putJson('/api/v1/notifications/preferences', [
                'channels' => ['email'],
                'days'     => [99],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('days.0');
    }

    public function test_update_preferences_requires_authentication(): void
    {
        $this->putJson('/api/v1/notifications/preferences', [
            'channels' => ['email'],
            'days'     => [1],
        ])->assertUnauthorized();
    }
}
