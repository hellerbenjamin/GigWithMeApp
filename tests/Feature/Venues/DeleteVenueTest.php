<?php

namespace Tests\Feature\Venues;

use App\Models\Band;
use App\Models\Gig;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteVenueTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Band}
     */
    private function userInBand(string $role = 'owner'): array
    {
        $user = User::factory()->create();
        $band = Band::factory()->create();
        $user->bands()->attach($band, ['role' => $role]);

        return [$user, $band];
    }

    public function test_a_member_can_delete_their_bands_venue(): void
    {
        [$user, $band] = $this->userInBand('member');
        $venue = Venue::factory()->for($band)->create(['name' => 'The Echo Lounge']);

        $this->actingAs($user)
            ->delete("/venues/{$venue->id}")
            ->assertRedirect('/venues')
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('venues', ['id' => $venue->id]);
    }

    public function test_deleting_a_venue_keeps_gig_history_and_clears_the_venue(): void
    {
        [$user, $band] = $this->userInBand();
        $venue = Venue::factory()->for($band)->create();
        $gig = Gig::factory()->for($band)->create(['venue_id' => $venue->id]);

        $this->actingAs($user)->delete("/venues/{$venue->id}")->assertRedirect('/venues');

        // The gig survives; its venue reverts to TBD (nullOnDelete).
        $this->assertDatabaseHas('gigs', ['id' => $gig->id, 'venue_id' => null]);
    }

    public function test_another_bands_venue_cannot_be_deleted(): void
    {
        [$user] = $this->userInBand();
        // A venue on an unrelated band — must 404, not delete, and not leak.
        $foreign = Venue::factory()->create(['name' => 'Theirs']);

        $this->actingAs($user)
            ->delete("/venues/{$foreign->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('venues', ['id' => $foreign->id]);
    }

    public function test_missing_venue_404s(): void
    {
        [$user] = $this->userInBand();

        $this->actingAs($user)->delete('/venues/999999')->assertNotFound();
    }

    public function test_guests_cannot_delete_venues(): void
    {
        [, $band] = $this->userInBand();
        $venue = Venue::factory()->for($band)->create();

        $this->delete("/venues/{$venue->id}")->assertRedirect('/login');

        $this->assertDatabaseHas('venues', ['id' => $venue->id]);
    }
}
