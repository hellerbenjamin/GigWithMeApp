<?php

namespace Tests\Feature\Gigs;

use App\Models\Band;
use App\Models\Gig;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UpdateGigTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Band}
     */
    private function userInBand(): array
    {
        $user = User::factory()->create();
        $band = Band::factory()->create();
        $user->bands()->attach($band, ['role' => 'owner']);

        return [$user, $band];
    }

    public function test_edit_page_renders_with_the_gig_and_band_venues(): void
    {
        [$user, $band] = $this->userInBand();
        $venue = Venue::factory()->for($band)->create(['name' => 'The Echo Lounge']);
        $gig = Gig::factory()->for($band)->create([
            'venue_id' => $venue->id,
            'name' => 'Friday Night Headline',
            'date' => '2026-06-12',
            'start_time' => '20:00',
        ]);
        // Another band's venue must not be offered in the picker.
        Venue::factory()->create(['name' => 'Someone Elses Room']);

        $this->actingAs($user)
            ->get("/gigs/{$gig->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Gigs/Edit')
                ->where('gig.name', 'Friday Night Headline')
                ->where('gig.date', '2026-06-12')
                ->where('gig.startTime', '20:00')
                ->where('gig.venueId', $venue->id)
                ->has('venues', 1)
                ->where('venues.0.name', 'The Echo Lounge')
                ->has('types')
                ->has('statuses')
            );
    }

    public function test_a_gig_can_be_updated(): void
    {
        [$user, $band] = $this->userInBand();
        $venue = Venue::factory()->for($band)->create();
        $gig = Gig::factory()->for($band)->create([
            'name' => 'Old Name',
            'status' => 'pending',
            'date' => '2026-06-12',
        ]);

        $this->actingAs($user)->put("/gigs/{$gig->id}", [
            'type' => 'gig',
            'status' => 'confirmed',
            'name' => 'New Name',
            'venue_id' => $venue->id,
            'date' => '2026-07-04',
            'start_time' => '21:00',
            'end_time' => '23:30',
            'fee' => 1500,
            'currency' => 'EUR',
        ])->assertRedirect('/gigs')->assertSessionHas('success');

        $gig->refresh();
        $this->assertSame('New Name', $gig->name);
        $this->assertSame('confirmed', $gig->status->value);
        $this->assertSame($venue->id, $gig->venue_id);
        $this->assertSame('2026-07-04', $gig->date->toDateString());
        $this->assertSame('EUR', $gig->currency);
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        [$user, $band] = $this->userInBand();
        $gig = Gig::factory()->for($band)->create();

        $this->actingAs($user)->put("/gigs/{$gig->id}", [
            'type' => 'gig',
            'status' => 'pending',
            'date' => '2026-06-12',
            'start_time' => '21:00',
            'end_time' => '20:00',
            'currency' => 'USD',
        ])->assertSessionHasErrors('end_time');
    }

    public function test_a_venue_from_another_band_is_rejected(): void
    {
        [$user, $band] = $this->userInBand();
        $gig = Gig::factory()->for($band)->create();
        $otherVenue = Venue::factory()->create();

        $this->actingAs($user)->put("/gigs/{$gig->id}", [
            'type' => 'gig',
            'status' => 'pending',
            'date' => '2026-06-12',
            'venue_id' => $otherVenue->id,
            'currency' => 'USD',
        ])->assertSessionHasErrors('venue_id');
    }

    public function test_another_bands_gig_cannot_be_edited(): void
    {
        [$user] = $this->userInBand();
        // A gig on a band the user doesn't belong to.
        $foreign = Gig::factory()->create(['name' => 'Untouched']);

        $this->actingAs($user)->get("/gigs/{$foreign->id}/edit")->assertNotFound();

        $this->actingAs($user)->put("/gigs/{$foreign->id}", [
            'type' => 'gig',
            'status' => 'confirmed',
            'date' => '2026-06-12',
            'currency' => 'USD',
        ])->assertNotFound();

        $this->assertSame('Untouched', $foreign->fresh()->name);
    }

    public function test_guests_cannot_edit_gigs(): void
    {
        $gig = Gig::factory()->create();

        $this->get("/gigs/{$gig->id}/edit")->assertRedirect('/login');
        $this->put("/gigs/{$gig->id}", [])->assertRedirect('/login');
    }
}
