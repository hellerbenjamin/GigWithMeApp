<?php

namespace Tests\Feature\Public;

use App\Models\Band;
use App\Models\Gig;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicGigListTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_gig_list_renders_for_a_valid_slug(): void
    {
        $band = Band::factory()->create(['name' => 'The Night Owls', 'slug' => 'the-night-owls']);

        $this->get('/bands/the-night-owls/gigs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/BandGigs')
                ->where('band.name', 'The Night Owls')
            );
    }

    public function test_unknown_slug_404s(): void
    {
        $this->get('/bands/no-such-band/gigs')->assertNotFound();
    }

    public function test_only_confirmed_gigs_are_shown(): void
    {
        $band = Band::factory()->create(['slug' => 'the-owls']);
        Gig::factory()->for($band)->create(['status' => 'confirmed', 'date' => now()->addDays(7)]);
        Gig::factory()->for($band)->create(['status' => 'pending', 'date' => now()->addDays(14)]);
        Gig::factory()->for($band)->create(['status' => 'cancelled', 'date' => now()->addDays(21)]);

        $this->get('/bands/the-owls/gigs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('upcoming', 1)
            );
    }

    public function test_rehearsals_are_not_shown(): void
    {
        $band = Band::factory()->create(['slug' => 'the-owls']);
        Gig::factory()->for($band)->create(['status' => 'confirmed', 'type' => 'rehearsal', 'date' => now()->addDays(7)]);
        Gig::factory()->for($band)->create(['status' => 'confirmed', 'type' => 'gig', 'date' => now()->addDays(14)]);

        $this->get('/bands/the-owls/gigs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('upcoming', 1)
            );
    }

    public function test_gigs_split_into_upcoming_and_past(): void
    {
        $band = Band::factory()->create(['slug' => 'the-owls']);
        Gig::factory()->for($band)->create(['status' => 'confirmed', 'date' => now()->addDays(7)]);
        Gig::factory()->for($band)->create(['status' => 'confirmed', 'date' => now()->subDays(7)]);

        $this->get('/bands/the-owls/gigs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('upcoming', 1)
                ->has('past', 1)
            );
    }

    public function test_fee_and_notes_are_not_exposed(): void
    {
        $band = Band::factory()->create(['slug' => 'the-owls']);
        Gig::factory()->for($band)->create([
            'status' => 'confirmed',
            'date' => now()->addDays(7),
            'fee' => 500.00,
            'notes' => 'Private booking notes',
        ]);

        $this->get('/bands/the-owls/gigs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('upcoming', 1, fn (Assert $gig) => $gig
                    ->missing('fee')
                    ->missing('notes')
                    ->etc()
                )
            );
    }

    public function test_page_is_accessible_without_login(): void
    {
        $band = Band::factory()->create(['slug' => 'the-owls']);

        $this->assertGuest();
        $this->get('/bands/the-owls/gigs')->assertOk();
    }
}
