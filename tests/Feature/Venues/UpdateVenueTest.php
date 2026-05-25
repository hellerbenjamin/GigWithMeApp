<?php

namespace Tests\Feature\Venues;

use App\Models\Band;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UpdateVenueTest extends TestCase
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

    public function test_edit_page_renders_with_the_venue(): void
    {
        [$user, $band] = $this->userInBand();
        $venue = Venue::factory()->for($band)->create([
            'name' => 'The Echo Lounge',
            'city' => 'Portland',
            'contact_person' => 'Jordan Reyes',
        ]);

        $this->actingAs($user)
            ->get("/venues/{$venue->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Venues/Edit')
                ->where('venue.id', $venue->id)
                ->where('venue.name', 'The Echo Lounge')
                ->where('venue.city', 'Portland')
                ->where('venue.contact_person', 'Jordan Reyes')
            );
    }

    public function test_a_venue_can_be_updated(): void
    {
        [$user, $band] = $this->userInBand();
        $venue = Venue::factory()->for($band)->create([
            'name' => 'Old Name',
            'city' => 'Portland',
        ]);

        $this->actingAs($user)->put("/venues/{$venue->id}", [
            'name' => 'New Name',
            'city' => 'Seattle',
            'website' => 'https://newname.test',
            'contact_person' => 'Sam Vega',
        ])->assertRedirect('/venues')->assertSessionHas('success');

        $venue->refresh();
        $this->assertSame('New Name', $venue->name);
        $this->assertSame('Seattle', $venue->city);
        $this->assertSame('Sam Vega', $venue->contact_person);
    }

    public function test_name_is_required(): void
    {
        [$user, $band] = $this->userInBand();
        $venue = Venue::factory()->for($band)->create(['name' => 'Keep Me']);

        $this->actingAs($user)
            ->put("/venues/{$venue->id}", ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->assertSame('Keep Me', $venue->fresh()->name);
    }

    public function test_email_and_website_are_validated(): void
    {
        [$user, $band] = $this->userInBand();
        $venue = Venue::factory()->for($band)->create();

        $this->actingAs($user)
            ->put("/venues/{$venue->id}", [
                'name' => 'Bad Data Hall',
                'email' => 'not-an-email',
                'website' => 'not a url',
            ])
            ->assertSessionHasErrors(['email', 'website']);
    }

    public function test_another_bands_venue_cannot_be_edited(): void
    {
        [$user] = $this->userInBand();
        // A venue on a band the user doesn't belong to.
        $foreign = Venue::factory()->create(['name' => 'Untouched']);

        $this->actingAs($user)->get("/venues/{$foreign->id}/edit")->assertNotFound();

        $this->actingAs($user)->put("/venues/{$foreign->id}", [
            'name' => 'Hijacked',
        ])->assertNotFound();

        $this->assertSame('Untouched', $foreign->fresh()->name);
    }

    public function test_guests_cannot_edit_venues(): void
    {
        $venue = Venue::factory()->create();

        $this->get("/venues/{$venue->id}/edit")->assertRedirect('/login');
        $this->put("/venues/{$venue->id}", [])->assertRedirect('/login');
    }
}
