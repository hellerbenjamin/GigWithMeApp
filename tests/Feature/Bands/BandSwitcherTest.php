<?php

namespace Tests\Feature\Bands;

use App\Enums\BandUserRoleEnum;
use App\Models\Band;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BandSwitcherTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Band}
     */
    private function userInBand(BandUserRoleEnum $role = BandUserRoleEnum::Owner): array
    {
        $user = User::factory()->create();
        $band = Band::factory()->create();
        $user->bands()->attach($band, ['role' => $role->value]);

        return [$user, $band];
    }

    public function test_dashboard_shares_active_band_and_band_list(): void
    {
        [$user, $band] = $this->userInBand();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('activeBand.id', $band->id)
                ->where('activeBand.role', 'owner')
                ->has('bands', 1)
                ->where('bands.0.id', $band->id)
            );
    }

    public function test_first_band_is_auto_selected_when_none_is_active(): void
    {
        $user = User::factory()->create();
        $first = Band::factory()->create(['name' => 'Aardvarks']);
        $later = Band::factory()->create(['name' => 'Zebras']);
        $user->bands()->attach($first, ['role' => 'member']);
        $user->bands()->attach($later, ['role' => 'owner']);

        // No active band in the session yet → the alphabetically-first wins.
        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->where('activeBand.id', $first->id));
    }

    public function test_session_active_band_drives_the_shared_prop(): void
    {
        $user = User::factory()->create();
        $a = Band::factory()->create();
        $b = Band::factory()->create();
        $user->bands()->attach($a, ['role' => 'owner']);
        $user->bands()->attach($b, ['role' => 'member']);

        $this->actingAs($user)
            ->withSession(['active_band_id' => $b->id])
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->where('activeBand.id', $b->id));
    }

    public function test_user_can_switch_active_band(): void
    {
        [$user, $current] = $this->userInBand();
        $target = Band::factory()->create();
        $user->bands()->attach($target, ['role' => 'member']);

        $this->actingAs($user)
            ->post("/bands/{$target->id}/set-active")
            ->assertRedirect()
            ->assertSessionHas('active_band_id', $target->id)
            ->assertSessionHas('success');
    }

    public function test_user_cannot_activate_a_band_they_do_not_belong_to(): void
    {
        [$user] = $this->userInBand();
        $foreign = Band::factory()->create();

        $this->actingAs($user)
            ->post("/bands/{$foreign->id}/set-active")
            ->assertForbidden();

        // The forbidden band must never become active. (The user's own band may
        // have been auto-selected by HasActiveBand before the abort — that's fine.)
        $this->assertNotEquals($foreign->id, session('active_band_id'));
    }

    public function test_a_stale_session_band_outside_the_users_bands_is_ignored(): void
    {
        [$user, $own] = $this->userInBand();
        $foreign = Band::factory()->create();

        // A session pointing at a band they don't belong to must not leak it;
        // they fall back to auto-selecting one of their own.
        $this->actingAs($user)
            ->withSession(['active_band_id' => $foreign->id])
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->where('activeBand.id', $own->id));
    }

    public function test_user_with_no_bands_is_redirected_to_band_creation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/bands/create');
    }

    public function test_switching_remembers_the_band_on_the_user(): void
    {
        [$user, $current] = $this->userInBand();
        $target = Band::factory()->create();
        $user->bands()->attach($target, ['role' => 'member']);

        $this->actingAs($user)
            ->post("/bands/{$target->id}/set-active")
            ->assertRedirect();

        $this->assertSame($target->id, $user->fresh()->last_active_band_id);
    }

    public function test_last_active_band_is_restored_in_a_fresh_session(): void
    {
        $user = User::factory()->create();
        $first = Band::factory()->create(['name' => 'Aardvarks']);
        $later = Band::factory()->create(['name' => 'Zebras']);
        $user->bands()->attach($first, ['role' => 'member']);
        $user->bands()->attach($later, ['role' => 'owner']);

        // They were last working in Zebras; a session with no active band (e.g.
        // after a reseed) must restore that, not re-default to alphabetical.
        $user->forceFill(['last_active_band_id' => $later->id])->save();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->where('activeBand.id', $later->id));
    }

    public function test_falls_back_to_first_band_when_remembered_band_is_no_longer_a_membership(): void
    {
        $user = User::factory()->create();
        $own = Band::factory()->create(['name' => 'Aardvarks']);
        $left = Band::factory()->create(['name' => 'Zebras']);
        $user->bands()->attach($own, ['role' => 'owner']);

        // Points at a band they've since left → ignored, fall back to first.
        $user->forceFill(['last_active_band_id' => $left->id])->save();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->where('activeBand.id', $own->id));
    }
}
