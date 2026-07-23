<?php

namespace Tests\Feature\Api\Member;

use App\Models\Band;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BandMemberTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Band} */
    private function bandWithOwner(): array
    {
        $owner = User::factory()->create();
        $band = Band::factory()->create();
        $owner->bands()->attach($band, ['role' => 'owner', 'critical' => true]);

        return [$owner, $band];
    }

    private function attach(Band $band, string $role = 'member'): User
    {
        $user = User::factory()->create();
        $user->bands()->attach($band, ['role' => $role, 'critical' => true]);

        return $user;
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/bands/{band}/members
    // -------------------------------------------------------------------------

    public function test_index_returns_the_roster_with_roles_and_manage_flag(): void
    {
        [$owner, $band] = $this->bandWithOwner();
        $this->attach($band, 'member');

        $this->withToken($this->tokenFor($owner))
            ->getJson("/api/v1/bands/{$band->id}/members")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('can_manage', true)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'email', 'phone_number', 'role', 'role_label', 'critical', 'is_you']],
                'can_manage',
                'roles' => [['value', 'label']],
            ]);
    }

    public function test_index_is_readable_by_a_plain_member_without_manage(): void
    {
        [, $band] = $this->bandWithOwner();
        $member = $this->attach($band, 'member');

        $this->withToken($this->tokenFor($member))
            ->getJson("/api/v1/bands/{$band->id}/members")
            ->assertOk()
            ->assertJsonPath('can_manage', false);
    }

    public function test_index_denies_a_non_member_of_the_band(): void
    {
        [, $band] = $this->bandWithOwner();
        $outsider = $this->attach(Band::factory()->create(), 'owner');

        $this->withToken($this->tokenFor($outsider))
            ->getJson("/api/v1/bands/{$band->id}/members")
            ->assertForbidden();
    }

    public function test_index_requires_authentication(): void
    {
        [, $band] = $this->bandWithOwner();

        $this->getJson("/api/v1/bands/{$band->id}/members")->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/bands/{band}/members
    // -------------------------------------------------------------------------

    public function test_owner_can_add_a_member(): void
    {
        Notification::fake();
        [$owner, $band] = $this->bandWithOwner();

        $this->withToken($this->tokenFor($owner))
            ->postJson("/api/v1/bands/{$band->id}/members", [
                'name' => 'New Player',
                'email' => 'newplayer@example.com',
                'role' => 'member',
                'critical' => true,
            ])
            ->assertCreated()
            ->assertJsonCount(2, 'data');

        $this->assertDatabaseHas('users', ['email' => 'newplayer@example.com']);
    }

    public function test_adding_a_duplicate_email_is_rejected(): void
    {
        Notification::fake();
        [$owner, $band] = $this->bandWithOwner();
        $existing = $this->attach($band, 'member');

        $this->withToken($this->tokenFor($owner))
            ->postJson("/api/v1/bands/{$band->id}/members", [
                'name' => 'Dup',
                'email' => $existing->email,
                'role' => 'member',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_a_plain_member_cannot_add_members(): void
    {
        [, $band] = $this->bandWithOwner();
        $member = $this->attach($band, 'member');

        $this->withToken($this->tokenFor($member))
            ->postJson("/api/v1/bands/{$band->id}/members", [
                'name' => 'Nope',
                'email' => 'nope@example.com',
                'role' => 'member',
            ])
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // PUT /api/v1/bands/{band}/members/{user}
    // -------------------------------------------------------------------------

    public function test_owner_can_change_a_members_role(): void
    {
        [$owner, $band] = $this->bandWithOwner();
        $member = $this->attach($band, 'member');

        $this->withToken($this->tokenFor($owner))
            ->putJson("/api/v1/bands/{$band->id}/members/{$member->id}", [
                'name' => $member->name,
                'email' => $member->email,
                'role' => 'admin',
                'critical' => false,
            ])
            ->assertOk();

        $this->assertSame('admin', $band->getUserRole($member->fresh())->value);
    }

    public function test_demoting_the_last_owner_is_blocked(): void
    {
        [$owner, $band] = $this->bandWithOwner();

        $this->withToken($this->tokenFor($owner))
            ->putJson("/api/v1/bands/{$band->id}/members/{$owner->id}", [
                'name' => $owner->name,
                'email' => $owner->email,
                'role' => 'member',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');
    }

    public function test_demoting_an_owner_is_allowed_when_another_owner_remains(): void
    {
        [$owner, $band] = $this->bandWithOwner();
        $secondOwner = $this->attach($band, 'owner');

        $this->withToken($this->tokenFor($owner))
            ->putJson("/api/v1/bands/{$band->id}/members/{$secondOwner->id}", [
                'name' => $secondOwner->name,
                'email' => $secondOwner->email,
                'role' => 'member',
            ])
            ->assertOk();
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/bands/{band}/members/{user}
    // -------------------------------------------------------------------------

    public function test_owner_can_remove_a_member(): void
    {
        [$owner, $band] = $this->bandWithOwner();
        $member = $this->attach($band, 'member');

        $this->withToken($this->tokenFor($owner))
            ->deleteJson("/api/v1/bands/{$band->id}/members/{$member->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertFalse($band->users()->whereKey($member->id)->exists());
        // The account itself is left intact.
        $this->assertDatabaseHas('users', ['id' => $member->id]);
    }

    public function test_removing_the_last_owner_is_blocked(): void
    {
        [$owner, $band] = $this->bandWithOwner();

        $this->withToken($this->tokenFor($owner))
            ->deleteJson("/api/v1/bands/{$band->id}/members/{$owner->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user');
    }

    public function test_a_member_can_remove_themselves(): void
    {
        [, $band] = $this->bandWithOwner();
        $member = $this->attach($band, 'member');

        $this->withToken($this->tokenFor($member))
            ->deleteJson("/api/v1/bands/{$band->id}/members/{$member->id}")
            ->assertOk();

        $this->assertFalse($band->users()->whereKey($member->id)->exists());
    }

    public function test_a_member_cannot_remove_someone_else(): void
    {
        [$owner, $band] = $this->bandWithOwner();
        $member = $this->attach($band, 'member');

        $this->withToken($this->tokenFor($member))
            ->deleteJson("/api/v1/bands/{$band->id}/members/{$owner->id}")
            ->assertForbidden();
    }
}
