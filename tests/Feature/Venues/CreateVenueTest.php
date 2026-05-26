<?php

namespace Tests\Feature\Venues;

use App\Models\Band;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CreateVenueTest extends TestCase
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

    public function test_create_page_renders(): void
    {
        [$user] = $this->userInBand();

        $this->actingAs($user)
            ->get('/venues/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Venues/Create'));
    }

    public function test_user_can_add_a_venue_to_the_active_band(): void
    {
        [$user, $band] = $this->userInBand();

        $response = $this->actingAs($user)->post('/venues', [
            'name' => 'The Echo Lounge',
            'city' => 'Portland',
            'state' => 'OR',
            'country' => 'United States',
            'email' => 'booking@echo.test',
            'website' => 'https://echo.test',
            'contact_person' => 'Jordan Reyes',
        ]);

        $response->assertRedirect('/venues')->assertSessionHas('success');

        $this->assertDatabaseHas('venues', [
            'band_id' => $band->id,
            'name' => 'The Echo Lounge',
            'city' => 'Portland',
            'contact_person' => 'Jordan Reyes',
        ]);
    }

    public function test_name_is_required(): void
    {
        [$user] = $this->userInBand();

        $this->actingAs($user)
            ->post('/venues', ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('venues', 0);
    }

    public function test_email_and_website_are_validated(): void
    {
        [$user] = $this->userInBand();

        $this->actingAs($user)
            ->post('/venues', [
                'name' => 'Bad Data Hall',
                'email' => 'not-an-email',
                'website' => 'not a url',
            ])
            ->assertSessionHasErrors(['email', 'website']);

        $this->assertDatabaseCount('venues', 0);
    }

    public function test_index_lists_only_the_active_bands_venues(): void
    {
        [$user, $band] = $this->userInBand();
        Venue::factory()->for($band)->create(['name' => 'Ours']);
        // A venue belonging to an unrelated band must not leak in.
        Venue::factory()->create(['name' => 'Theirs']);

        $this->actingAs($user)
            ->get('/venues')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Venues/Index')
                ->has('venues.data', 1)
                ->where('venues.data.0.name', 'Ours')
            );
    }

    public function test_guests_cannot_reach_venue_creation(): void
    {
        $this->get('/venues/create')->assertRedirect('/login');
        $this->post('/venues', ['name' => 'Ghosts'])->assertRedirect('/login');
        $this->assertDatabaseCount('venues', 0);
    }
}
