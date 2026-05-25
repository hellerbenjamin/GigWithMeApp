<?php

namespace Tests\Feature\Gigs;

use App\Models\Band;
use App\Models\Gig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteGigTest extends TestCase
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

    public function test_a_member_can_delete_their_bands_gig(): void
    {
        [$user, $band] = $this->userInBand('member');
        $gig = Gig::factory()->for($band)->create(['name' => 'Friday Night Headline']);

        $this->actingAs($user)
            ->delete("/gigs/{$gig->id}")
            ->assertRedirect('/gigs')
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('gigs', ['id' => $gig->id]);
    }

    public function test_another_bands_gig_cannot_be_deleted(): void
    {
        [$user] = $this->userInBand();
        // A gig on an unrelated band — must 404, not delete, and not leak.
        $foreignGig = Gig::factory()->create(['name' => 'Theirs']);

        $this->actingAs($user)
            ->delete("/gigs/{$foreignGig->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('gigs', ['id' => $foreignGig->id]);
    }

    public function test_missing_gig_404s(): void
    {
        [$user] = $this->userInBand();

        $this->actingAs($user)->delete('/gigs/999999')->assertNotFound();
    }

    public function test_guests_cannot_delete_gigs(): void
    {
        [, $band] = $this->userInBand();
        $gig = Gig::factory()->for($band)->create();

        $this->delete("/gigs/{$gig->id}")->assertRedirect('/login');

        $this->assertDatabaseHas('gigs', ['id' => $gig->id]);
    }
}
