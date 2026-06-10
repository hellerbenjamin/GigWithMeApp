<?php

namespace Tests\Feature\BandMembers;

use App\Models\Band;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UpdateBandMemberTest extends TestCase
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

    public function test_edit_page_renders_for_a_manager(): void
    {
        [$owner, $band] = $this->userInBand('admin');
        $member = User::factory()->create(['name' => 'Jordan Reyes']);
        $member->bands()->attach($band, ['role' => 'member']);

        $this->actingAs($owner)
            ->get("/band-members/{$member->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('BandMembers/Edit')
                ->where('member.name', 'Jordan Reyes')
                ->where('member.role', 'member')
                ->has('roles')
            );
    }

    public function test_a_manager_can_update_details_and_role(): void
    {
        [$owner, $band] = $this->userInBand();
        $member = User::factory()->create();
        $member->bands()->attach($band, ['role' => 'member']);

        $this->actingAs($owner)->put("/band-members/{$member->id}", [
            'name' => '  Jordan Reyes  ',
            'email' => 'Jordan@Band.test',
            'role' => 'admin',
        ])->assertRedirect('/band-members')->assertSessionHas('success');

        $member->refresh();
        // Name trimmed, email lower-cased.
        $this->assertSame('Jordan Reyes', $member->name);
        $this->assertSame('jordan@band.test', $member->email);
        // Pivot role updated.
        $this->assertDatabaseHas('band_user', [
            'band_id' => $band->id,
            'user_id' => $member->id,
            'role' => 'admin',
        ]);
    }

    public function test_admin_edit_leaves_the_members_phone_untouched(): void
    {
        [$owner, $band] = $this->userInBand();
        // The phone is member-owned (set via the opt-in flow); an admin edit must
        // not change it, even if a phone_number is smuggled into the request.
        $member = User::factory()->create(['phone_number' => '+15550000000']);
        $member->bands()->attach($band, ['role' => 'member']);

        $this->actingAs($owner)->put("/band-members/{$member->id}", [
            'name' => 'Renamed',
            'email' => $member->email,
            'phone_number' => '+19999999999',
            'role' => 'member',
        ])->assertRedirect('/band-members');

        $this->assertSame('+15550000000', $member->fresh()->phone_number);
    }

    public function test_keeping_the_same_email_is_allowed(): void
    {
        [$owner, $band] = $this->userInBand();
        $member = User::factory()->create(['email' => 'keep@band.test']);
        $member->bands()->attach($band, ['role' => 'member']);

        $this->actingAs($owner)->put("/band-members/{$member->id}", [
            'name' => 'Renamed',
            'email' => 'keep@band.test',
            'role' => 'admin',
        ])->assertRedirect('/band-members')->assertSessionHasNoErrors();

        $this->assertSame('Renamed', $member->fresh()->name);
    }

    public function test_email_must_stay_unique_across_users(): void
    {
        [$owner, $band] = $this->userInBand();
        $member = User::factory()->create(['email' => 'mine@band.test']);
        $member->bands()->attach($band, ['role' => 'member']);
        User::factory()->create(['email' => 'taken@band.test']);

        $this->actingAs($owner)->put("/band-members/{$member->id}", [
            'name' => $member->name,
            'email' => 'taken@band.test',
            'role' => 'member',
        ])->assertSessionHasErrors('email');

        $this->assertSame('mine@band.test', $member->fresh()->email);
    }

    public function test_the_last_owner_cannot_be_demoted(): void
    {
        [$owner, $band] = $this->userInBand('owner');

        $this->actingAs($owner)->put("/band-members/{$owner->id}", [
            'name' => $owner->name,
            'email' => $owner->email,
            'role' => 'member',
        ])->assertSessionHas('error');

        // Still an owner — the band keeps one.
        $this->assertDatabaseHas('band_user', [
            'band_id' => $band->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);
    }

    public function test_an_owner_can_be_demoted_when_another_owner_remains(): void
    {
        [$owner, $band] = $this->userInBand('owner');
        $coOwner = User::factory()->create();
        $coOwner->bands()->attach($band, ['role' => 'owner']);

        $this->actingAs($owner)->put("/band-members/{$coOwner->id}", [
            'name' => $coOwner->name,
            'email' => $coOwner->email,
            'role' => 'member',
        ])->assertRedirect('/band-members')->assertSessionHas('success');

        $this->assertDatabaseHas('band_user', [
            'band_id' => $band->id,
            'user_id' => $coOwner->id,
            'role' => 'member',
        ]);
    }

    public function test_plain_members_cannot_edit_anyone(): void
    {
        [$member, $band] = $this->userInBand('member');
        $other = User::factory()->create(['name' => 'Untouched']);
        $other->bands()->attach($band, ['role' => 'member']);

        $this->actingAs($member)->get("/band-members/{$other->id}/edit")->assertForbidden();

        $this->actingAs($member)->put("/band-members/{$other->id}", [
            'name' => 'Hacked',
            'email' => $other->email,
            'role' => 'owner',
        ])->assertForbidden();

        $this->assertSame('Untouched', $other->fresh()->name);
    }

    public function test_a_user_outside_the_active_band_cannot_be_edited(): void
    {
        [$owner] = $this->userInBand('owner');
        $stranger = User::factory()->create();

        $this->actingAs($owner)->get("/band-members/{$stranger->id}/edit")->assertNotFound();
        $this->actingAs($owner)->put("/band-members/{$stranger->id}", [
            'name' => 'Nope',
            'email' => $stranger->email,
            'role' => 'member',
        ])->assertNotFound();
    }

    public function test_guests_cannot_edit_members(): void
    {
        [$member] = $this->userInBand('member');

        $this->get("/band-members/{$member->id}/edit")->assertRedirect('/login');
        $this->put("/band-members/{$member->id}", [])->assertRedirect('/login');
    }
}
