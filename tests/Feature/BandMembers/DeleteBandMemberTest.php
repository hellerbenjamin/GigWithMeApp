<?php

namespace Tests\Feature\BandMembers;

use App\Models\Band;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteBandMemberTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A user attached to a fresh band in the given role, with that band active.
     *
     * @return array{0: User, 1: Band}
     */
    private function userInBand(string $role = 'owner'): array
    {
        $user = User::factory()->create();
        $band = Band::factory()->create();
        $user->bands()->attach($band, ['role' => $role]);

        return [$user, $band];
    }

    public function test_a_manager_can_remove_another_member(): void
    {
        [$owner, $band] = $this->userInBand();
        $member = User::factory()->create();
        $member->bands()->attach($band, ['role' => 'member']);

        $this->actingAs($owner)
            ->delete("/band-members/{$member->id}")
            ->assertRedirect('/band-members')
            ->assertSessionHas('success');

        // Only the pivot is gone; the account itself survives.
        $this->assertDatabaseMissing('band_user', [
            'band_id' => $band->id,
            'user_id' => $member->id,
        ]);
        $this->assertModelExists($member);
    }

    public function test_the_last_owner_cannot_be_removed(): void
    {
        [$owner, $band] = $this->userInBand('owner');

        $this->actingAs($owner)
            ->delete("/band-members/{$owner->id}")
            ->assertSessionHas('error');

        // Still on the roster — the band keeps an owner.
        $this->assertDatabaseHas('band_user', [
            'band_id' => $band->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_an_owner_can_be_removed_when_another_owner_remains(): void
    {
        [$owner, $band] = $this->userInBand('owner');
        $coOwner = User::factory()->create();
        $coOwner->bands()->attach($band, ['role' => 'owner']);

        $this->actingAs($owner)
            ->delete("/band-members/{$coOwner->id}")
            ->assertRedirect('/band-members')
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('band_user', [
            'band_id' => $band->id,
            'user_id' => $coOwner->id,
        ]);
    }

    public function test_an_owner_can_leave_when_another_owner_remains(): void
    {
        [$owner, $band] = $this->userInBand('owner');
        $coOwner = User::factory()->create();
        $coOwner->bands()->attach($band, ['role' => 'owner']);

        // Removing yourself is "leaving" — allowed as long as an owner remains.
        $this->actingAs($owner)
            ->delete("/band-members/{$owner->id}")
            ->assertRedirect('/band-members')
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('band_user', [
            'band_id' => $band->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_plain_members_cannot_remove_anyone(): void
    {
        [$member, $band] = $this->userInBand('member');
        $other = User::factory()->create();
        $other->bands()->attach($band, ['role' => 'member']);

        $this->actingAs($member)
            ->delete("/band-members/{$other->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('band_user', [
            'band_id' => $band->id,
            'user_id' => $other->id,
        ]);
    }

    public function test_a_user_outside_the_active_band_cannot_be_removed(): void
    {
        [$owner] = $this->userInBand('owner');
        $stranger = User::factory()->create(); // not on this band

        $this->actingAs($owner)
            ->delete("/band-members/{$stranger->id}")
            ->assertNotFound();
    }

    public function test_guests_cannot_remove_members(): void
    {
        [$member, $band] = $this->userInBand('member');

        $this->delete("/band-members/{$member->id}")->assertRedirect('/login');

        $this->assertDatabaseHas('band_user', [
            'band_id' => $band->id,
            'user_id' => $member->id,
        ]);
    }
}
