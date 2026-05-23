<?php

namespace Tests\Feature\BandMembers;

use App\Models\Band;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CreateBandMemberTest extends TestCase
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

    public function test_create_page_renders_for_a_manager(): void
    {
        [$user] = $this->userInBand('admin');

        $this->actingAs($user)
            ->get('/band-members/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('BandMembers/Create')->has('roles'));
    }

    public function test_an_existing_user_is_attached_without_a_new_account(): void
    {
        [$owner, $band] = $this->userInBand();
        $existing = User::factory()->create(['email' => 'jordan@band.test', 'name' => 'Jordan Reyes']);

        $response = $this->actingAs($owner)->post('/band-members', [
            'name' => 'Ignored Name',
            'email' => 'jordan@band.test',
            'role' => 'admin',
        ]);

        $response->assertRedirect('/band-members')->assertSessionHas('success');

        // No second account was created for the same email.
        $this->assertSame(1, User::where('email', 'jordan@band.test')->count());
        $this->assertDatabaseHas('band_user', [
            'band_id' => $band->id,
            'user_id' => $existing->id,
            'role' => 'admin',
        ]);
        // Their own account name is untouched.
        $this->assertSame('Jordan Reyes', $existing->fresh()->name);
    }

    public function test_an_unknown_email_creates_an_account_and_attaches_it(): void
    {
        [$owner, $band] = $this->userInBand();

        $this->actingAs($owner)->post('/band-members', [
            'name' => 'Sam Rivera',
            'email' => 'Sam@Band.test',
            'role' => 'member',
        ])->assertRedirect('/band-members');

        // Email is normalised to lower case before storage.
        $created = User::where('email', 'sam@band.test')->first();
        $this->assertNotNull($created);
        $this->assertSame('Sam Rivera', $created->name);
        $this->assertDatabaseHas('band_user', [
            'band_id' => $band->id,
            'user_id' => $created->id,
            'role' => 'member',
        ]);
    }

    public function test_a_person_already_on_the_roster_is_rejected(): void
    {
        [$owner, $band] = $this->userInBand();
        $member = User::factory()->create(['email' => 'taken@band.test']);
        $member->bands()->attach($band, ['role' => 'member']);

        $this->actingAs($owner)->post('/band-members', [
            'name' => 'Dup',
            'email' => 'taken@band.test',
            'role' => 'admin',
        ])->assertSessionHasErrors('email');

        // Role wasn't changed by the rejected request.
        $this->assertDatabaseHas('band_user', [
            'band_id' => $band->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);
    }

    public function test_name_email_and_role_are_validated(): void
    {
        [$owner] = $this->userInBand();

        $this->actingAs($owner)->post('/band-members', [
            'name' => '',
            'email' => 'not-an-email',
            'role' => 'roadie',
        ])->assertSessionHasErrors(['name', 'email', 'role']);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_plain_members_cannot_manage_the_roster(): void
    {
        [$member, $band] = $this->userInBand('member');

        $this->actingAs($member)->get('/band-members/create')->assertForbidden();

        $this->actingAs($member)->post('/band-members', [
            'name' => 'Nope',
            'email' => 'nope@band.test',
            'role' => 'member',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'nope@band.test']);
    }

    public function test_members_can_still_view_the_roster(): void
    {
        [$member] = $this->userInBand('member');

        $this->actingAs($member)
            ->get('/band-members')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('BandMembers/Index')
                ->where('canManage', false)
                ->has('members', 1)
            );
    }

    public function test_guests_cannot_reach_member_creation(): void
    {
        $this->get('/band-members/create')->assertRedirect('/login');
        $this->post('/band-members', ['email' => 'ghost@band.test'])->assertRedirect('/login');
        $this->assertDatabaseMissing('users', ['email' => 'ghost@band.test']);
    }
}
