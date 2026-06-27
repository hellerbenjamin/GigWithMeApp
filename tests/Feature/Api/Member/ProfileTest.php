<?php

namespace Tests\Feature\Api\Member;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // GET /api/v1/profile
    // -------------------------------------------------------------------------

    public function test_show_returns_profile(): void
    {
        $user = User::factory()->create(['timezone' => 'America/New_York']);

        $this->withToken($user->createToken('test')->plainTextToken)
            ->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'timezone', 'avatar_url']])
            ->assertJsonPath('data.timezone', 'America/New_York')
            ->assertJsonPath('data.avatar_url', null);
    }

    public function test_show_requires_authentication(): void
    {
        $this->getJson('/api/v1/profile')->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // PUT /api/v1/profile
    // -------------------------------------------------------------------------

    public function test_update_saves_name_and_timezone(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->putJson('/api/v1/profile', [
                'name'     => 'Jordan Reyes',
                'timezone' => 'America/Chicago',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Jordan Reyes')
            ->assertJsonPath('data.timezone', 'America/Chicago');

        $this->assertSame('Jordan Reyes', $user->fresh()->name);
        $this->assertSame('America/Chicago', $user->fresh()->timezone);
    }

    public function test_update_allows_null_timezone(): void
    {
        $user = User::factory()->create(['timezone' => 'America/New_York']);

        $this->withToken($user->createToken('test')->plainTextToken)
            ->putJson('/api/v1/profile', [
                'name'     => $user->name,
                'timezone' => null,
            ])
            ->assertOk()
            ->assertJsonPath('data.timezone', null);
    }

    public function test_update_rejects_invalid_timezone(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->putJson('/api/v1/profile', [
                'name'     => $user->name,
                'timezone' => 'Not/ATimezone',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('timezone');
    }

    public function test_update_requires_name(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->putJson('/api/v1/profile', ['name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_update_requires_authentication(): void
    {
        $this->putJson('/api/v1/profile', ['name' => 'Test'])->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/profile/avatar
    // -------------------------------------------------------------------------

    public function test_avatar_upload_stores_file_and_returns_url(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('photo.jpg', 200, 200),
            ])
            ->assertOk()
            ->assertJsonPath('data.avatar_url', fn ($url) => str_contains($url, 'avatars/'));

        Storage::disk('public')->assertExists('avatars/' . pathinfo($user->fresh()->avatar_path, PATHINFO_BASENAME));
    }

    public function test_avatar_upload_replaces_existing_avatar(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        // Upload first avatar.
        $this->withToken($user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('first.jpg'),
            ])->assertOk();

        $firstPath = $user->fresh()->avatar_path;
        Storage::disk('public')->assertExists($firstPath);

        // Upload second avatar — first should be deleted.
        $this->withToken($user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('second.jpg'),
            ])->assertOk();

        Storage::disk('public')->assertMissing($firstPath);
    }

    public function test_avatar_rejects_non_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/profile/avatar', [
                'avatar' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('avatar');
    }

    public function test_avatar_requires_authentication(): void
    {
        Storage::fake('public');

        $this->postJson('/api/v1/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('photo.jpg'),
        ])->assertUnauthorized();
    }
}
