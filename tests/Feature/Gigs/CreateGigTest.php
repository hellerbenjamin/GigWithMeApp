<?php

namespace Tests\Feature\Gigs;

use App\Models\Band;
use App\Models\Gig;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CreateGigTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Booking a gig can fire SMS notifications (auto-confirm / poll); keep
        // them off the wire in tests.
        Notification::fake();
    }

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

    public function test_create_page_renders_with_the_active_bands_venues(): void
    {
        [$user, $band] = $this->userInBand();
        Venue::factory()->for($band)->create(['name' => 'The Echo Lounge']);
        // Another band's venue must not be offered in the picker.
        Venue::factory()->create(['name' => 'Someone Elses Room']);

        $this->actingAs($user)
            ->get('/gigs/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Gigs/Create')
                ->has('venues', 1)
                ->where('venues.0.name', 'The Echo Lounge')
                ->has('types')
                ->has('bookingModes')
                ->where('defaultBookingMode', 'auto')
            );
    }

    public function test_create_page_includes_venue_gig_defaults_for_prefill(): void
    {
        [$user, $band] = $this->userInBand();
        Venue::factory()->for($band)->withGigDefaults()->create(['name' => 'The Echo Lounge']);

        $this->actingAs($user)
            ->get('/gigs/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Gigs/Create')
                ->has('venues', 1)
                ->where('venues.0.default_load_in_time', '16:00:00')
                ->where('venues.0.default_soundcheck_time', '17:30:00')
                ->where('venues.0.default_doors_time', '19:00:00')
                ->where('venues.0.default_start_time', '20:00:00')
                ->where('venues.0.default_end_time', '22:30:00')
                ->where('venues.0.default_notes', 'Backline provided. Load in via the alley.')
            );
    }

    public function test_user_can_book_a_gig(): void
    {
        [$user, $band] = $this->userInBand();
        $venue = Venue::factory()->for($band)->create();

        $response = $this->actingAs($user)->post('/gigs', [
            'type' => 'gig',
            'booking_mode' => 'auto',
            'name' => 'Friday Night Headline',
            'venue_id' => $venue->id,
            'date' => '2026-06-12',
            'doors_time' => '19:00',
            'start_time' => '20:00',
            'end_time' => '22:30',
            'fee' => 1200,
            'currency' => 'USD',
        ]);

        $response->assertRedirect('/gigs')->assertSessionHas('success');

        $this->assertDatabaseHas('gigs', [
            'band_id' => $band->id,
            'venue_id' => $venue->id,
            'name' => 'Friday Night Headline',
            'status' => 'confirmed',
        ]);
        $this->assertSame('2026-06-12', Gig::sole()->date->toDateString());
    }

    public function test_a_gig_can_be_booked_without_a_venue(): void
    {
        [$user, $band] = $this->userInBand();

        $this->actingAs($user)->post('/gigs', [
            'type' => 'gig',
            'booking_mode' => 'auto',
            'date' => '2026-07-01',
            'currency' => 'USD',
        ])->assertRedirect('/gigs');

        $this->assertDatabaseHas('gigs', [
            'band_id' => $band->id,
            'venue_id' => null,
        ]);
        $this->assertSame('2026-07-01', Gig::sole()->date->toDateString());
    }

    public function test_date_is_required(): void
    {
        [$user] = $this->userInBand();

        $this->actingAs($user)
            ->post('/gigs', ['type' => 'gig', 'status' => 'pending', 'currency' => 'USD'])
            ->assertSessionHasErrors('date');

        $this->assertDatabaseCount('gigs', 0);
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        [$user] = $this->userInBand();

        $this->actingAs($user)
            ->post('/gigs', [
                'type' => 'gig',
                'booking_mode' => 'auto',
                'date' => '2026-06-12',
                'start_time' => '21:00',
                'end_time' => '20:00',
                'currency' => 'USD',
            ])
            ->assertSessionHasErrors('end_time');

        $this->assertDatabaseCount('gigs', 0);
    }

    public function test_a_venue_from_another_band_is_rejected(): void
    {
        [$user] = $this->userInBand();
        $otherVenue = Venue::factory()->create();

        $this->actingAs($user)
            ->post('/gigs', [
                'type' => 'gig',
                'booking_mode' => 'auto',
                'date' => '2026-06-12',
                'venue_id' => $otherVenue->id,
                'currency' => 'USD',
            ])
            ->assertSessionHasErrors('venue_id');

        $this->assertDatabaseCount('gigs', 0);
    }

    public function test_index_lists_only_the_active_bands_gigs(): void
    {
        [$user, $band] = $this->userInBand();
        Gig::factory()->for($band)->create(['name' => 'Ours']);
        // A gig belonging to an unrelated band must not leak in.
        Gig::factory()->create(['name' => 'Theirs']);

        $this->actingAs($user)
            ->get('/gigs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Gigs/Index')
                ->has('gigs', 1)
                ->where('gigs.0.name', 'Ours')
            );
    }

    public function test_guests_cannot_reach_gig_creation(): void
    {
        $this->get('/gigs/create')->assertRedirect('/login');
        $this->post('/gigs', ['date' => '2026-06-12'])->assertRedirect('/login');
        $this->assertDatabaseCount('gigs', 0);
    }
}
