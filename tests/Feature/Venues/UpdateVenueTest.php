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

    public function test_edit_page_includes_gig_defaults(): void
    {
        [$user, $band] = $this->userInBand();
        $venue = Venue::factory()->for($band)->withGigDefaults()->create([
            'name' => 'The Echo Lounge',
        ]);

        $this->actingAs($user)
            ->get("/venues/{$venue->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Venues/Edit')
                ->where('venue.default_load_in_time', '16:00:00')
                ->where('venue.default_soundcheck_time', '17:30:00')
                ->where('venue.default_doors_time', '19:00:00')
                ->where('venue.default_start_time', '20:00:00')
                ->where('venue.default_end_time', '22:30:00')
                ->where('venue.default_notes', 'Backline provided. Load in via the alley.')
            );
    }

    public function test_gig_defaults_can_be_updated(): void
    {
        [$user, $band] = $this->userInBand();
        $venue = Venue::factory()->for($band)->create(['name' => 'The Echo Lounge']);

        $this->actingAs($user)->put("/venues/{$venue->id}", [
            'name' => 'The Echo Lounge',
            'default_load_in_time' => '15:00',
            'default_doors_time' => '18:30',
            'default_start_time' => '19:30',
            'default_end_time' => '21:00',
            'default_notes' => 'Park out back.',
        ])->assertRedirect('/venues')->assertSessionHas('success');

        $venue->refresh();
        $this->assertSame('15:00:00', $venue->default_load_in_time);
        $this->assertSame('18:30:00', $venue->default_doors_time);
        $this->assertSame('19:30:00', $venue->default_start_time);
        $this->assertSame('21:00:00', $venue->default_end_time);
        $this->assertSame('Park out back.', $venue->default_notes);
        $this->assertNull($venue->default_soundcheck_time);
    }

    public function test_gig_defaults_can_be_cleared_on_update(): void
    {
        [$user, $band] = $this->userInBand();
        $venue = Venue::factory()->for($band)->withGigDefaults()->create(['name' => 'The Echo Lounge']);

        // The frontend sends explicit null for cleared time pickers and a blank
        // string for a cleared notes textarea (the transform converts it to null).
        $this->actingAs($user)->put("/venues/{$venue->id}", [
            'name' => 'The Echo Lounge',
            'default_load_in_time' => null,
            'default_soundcheck_time' => null,
            'default_doors_time' => null,
            'default_start_time' => null,
            'default_end_time' => null,
            'default_notes' => null,
        ])->assertRedirect('/venues');

        $venue->refresh();
        $this->assertNull($venue->default_load_in_time);
        $this->assertNull($venue->default_start_time);
        $this->assertNull($venue->default_notes);
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
