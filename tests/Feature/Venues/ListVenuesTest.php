<?php

namespace Tests\Feature\Venues;

use App\Models\Band;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ListVenuesTest extends TestCase
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

    public function test_index_lists_the_active_bands_venues(): void
    {
        [$user, $band] = $this->userInBand();
        Venue::factory()->count(3)->create(['band_id' => $band->id]);

        $this->actingAs($user)
            ->get('/venues')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Venues/Index')
                ->where('hasVenues', true)
                ->where('venues.total', 3)
                ->has('venues.data', 3)
            );
    }

    public function test_search_matches_name_city_and_contact_case_insensitively(): void
    {
        [$user, $band] = $this->userInBand();
        $match = Venue::factory()->create(['band_id' => $band->id, 'name' => 'The Echo Lounge', 'city' => 'Portland', 'contact_person' => 'Sam']);
        $byCity = Venue::factory()->create(['band_id' => $band->id, 'name' => 'Riverside', 'city' => 'Echo Park', 'contact_person' => 'Jo']);
        Venue::factory()->create(['band_id' => $band->id, 'name' => 'The Basement', 'city' => 'Austin', 'contact_person' => 'Lee']);

        // Lowercase term still matches "Echo" in name and city (ilike).
        $this->actingAs($user)
            ->get('/venues?search=echo')
            ->assertInertia(fn (Assert $page) => $page
                ->where('venues.total', 2)
                ->where('venues.data', fn ($data) => $data->pluck('id')->sort()->values()->all()
                    === collect([$match->id, $byCity->id])->sort()->values()->all())
            );
    }

    public function test_country_and_state_filters_narrow_results(): void
    {
        [$user, $band] = $this->userInBand();
        $or = Venue::factory()->create(['band_id' => $band->id, 'country' => 'United States', 'state' => 'OR']);
        Venue::factory()->create(['band_id' => $band->id, 'country' => 'United States', 'state' => 'TX']);
        Venue::factory()->create(['band_id' => $band->id, 'country' => 'Canada', 'state' => 'BC']);

        $this->actingAs($user)
            ->get('/venues?country=United+States')
            ->assertInertia(fn (Assert $page) => $page->where('venues.total', 2));

        $this->actingAs($user)
            ->get('/venues?country=United+States&state=OR')
            ->assertInertia(fn (Assert $page) => $page
                ->where('venues.total', 1)
                ->where('venues.data.0.id', $or->id)
            );
    }

    public function test_state_options_cascade_off_the_selected_country(): void
    {
        [$user, $band] = $this->userInBand();
        Venue::factory()->create(['band_id' => $band->id, 'country' => 'United States', 'state' => 'OR']);
        Venue::factory()->create(['band_id' => $band->id, 'country' => 'Canada', 'state' => 'BC']);

        $this->actingAs($user)
            ->get('/venues?country=United+States')
            ->assertInertia(fn (Assert $page) => $page
                ->where('filterOptions.countries', fn ($c) => $c->all() === ['Canada', 'United States'])
                ->where('filterOptions.states', fn ($s) => $s->all() === ['OR'])
            );
    }

    public function test_has_contact_filter_excludes_venues_without_a_contact(): void
    {
        [$user, $band] = $this->userInBand();
        $withPerson = Venue::factory()->create(['band_id' => $band->id, 'contact_person' => 'Sam', 'contact_phone' => null]);
        $withPhone = Venue::factory()->create(['band_id' => $band->id, 'contact_person' => null, 'contact_phone' => '555-0100']);
        Venue::factory()->create(['band_id' => $band->id, 'contact_person' => null, 'contact_phone' => null]);

        $this->actingAs($user)
            ->get('/venues?has_contact=1')
            ->assertInertia(fn (Assert $page) => $page
                ->where('venues.total', 2)
                ->where('venues.data', fn ($data) => $data->pluck('id')->sort()->values()->all()
                    === collect([$withPerson->id, $withPhone->id])->sort()->values()->all())
            );
    }

    public function test_sort_options(): void
    {
        [$user, $band] = $this->userInBand();
        $beta = Venue::factory()->create(['band_id' => $band->id, 'name' => 'Beta', 'city' => 'Austin', 'created_at' => now()->subDays(2)]);
        $alpha = Venue::factory()->create(['band_id' => $band->id, 'name' => 'Alpha', 'city' => 'Boston', 'created_at' => now()->subDay()]);
        $gamma = Venue::factory()->create(['band_id' => $band->id, 'name' => 'Gamma', 'city' => 'Atlanta', 'created_at' => now()]);

        // Default: name A–Z.
        $this->actingAs($user)
            ->get('/venues')
            ->assertInertia(fn (Assert $page) => $page->where('venues.data', fn ($d) => $d->pluck('id')->all()
                === [$alpha->id, $beta->id, $gamma->id]));

        // Recently added: created_at desc.
        $this->actingAs($user)
            ->get('/venues?sort=recent')
            ->assertInertia(fn (Assert $page) => $page->where('venues.data', fn ($d) => $d->pluck('id')->all()
                === [$gamma->id, $alpha->id, $beta->id]));

        // City A–Z (Atlanta, Austin, Boston).
        $this->actingAs($user)
            ->get('/venues?sort=city')
            ->assertInertia(fn (Assert $page) => $page->where('venues.data', fn ($d) => $d->pluck('id')->all()
                === [$gamma->id, $beta->id, $alpha->id]));
    }

    public function test_invalid_sort_falls_back_to_name(): void
    {
        [$user, $band] = $this->userInBand();
        Venue::factory()->create(['band_id' => $band->id, 'name' => 'Beta']);
        Venue::factory()->create(['band_id' => $band->id, 'name' => 'Alpha']);

        $this->actingAs($user)
            ->get('/venues?sort=created_at;drop')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.sort', 'name')
                ->where('venues.data.0.name', 'Alpha')
            );
    }

    public function test_results_are_paginated_at_twelve_per_page(): void
    {
        [$user, $band] = $this->userInBand();
        Venue::factory()->count(15)->create(['band_id' => $band->id]);

        $this->actingAs($user)
            ->get('/venues')
            ->assertInertia(fn (Assert $page) => $page
                ->where('venues.total', 15)
                ->where('venues.per_page', 12)
                ->where('venues.last_page', 2)
                ->has('venues.data', 12)
            );

        $this->actingAs($user)
            ->get('/venues?page=2')
            ->assertInertia(fn (Assert $page) => $page->has('venues.data', 3));
    }

    public function test_active_filters_ride_along_on_pagination_links(): void
    {
        [$user, $band] = $this->userInBand();
        Venue::factory()->count(15)->create(['band_id' => $band->id, 'name' => 'Club Night']);

        // 15 matches → there's a page 2, and its link must keep the search term.
        $this->actingAs($user)
            ->get('/venues?search=club')
            ->assertInertia(fn (Assert $page) => $page
                ->where('venues.total', 15)
                ->where('venues.next_page_url', fn ($url) => str_contains($url, 'search=club'))
            );
    }

    public function test_filtering_to_nothing_is_distinct_from_having_no_venues(): void
    {
        [$user, $band] = $this->userInBand();
        Venue::factory()->create(['band_id' => $band->id, 'name' => 'The Echo Lounge']);

        // Band has venues, but nothing matches → empty results, hasVenues true.
        $this->actingAs($user)
            ->get('/venues?search=zzzznope')
            ->assertInertia(fn (Assert $page) => $page
                ->where('hasVenues', true)
                ->where('venues.total', 0)
                ->has('venues.data', 0)
            );
    }

    public function test_a_band_with_no_venues_reports_has_venues_false(): void
    {
        [$user] = $this->userInBand();

        $this->actingAs($user)
            ->get('/venues')
            ->assertInertia(fn (Assert $page) => $page
                ->where('hasVenues', false)
                ->where('venues.total', 0)
            );
    }

    public function test_another_bands_venues_never_appear(): void
    {
        [$user, $band] = $this->userInBand();
        $mine = Venue::factory()->create(['band_id' => $band->id, 'name' => 'Mine']);

        $otherBand = Band::factory()->create();
        Venue::factory()->create(['band_id' => $otherBand->id, 'name' => 'Mine']); // same name, different band

        $this->actingAs($user)
            ->get('/venues?search=mine')
            ->assertInertia(fn (Assert $page) => $page
                ->where('venues.total', 1)
                ->where('venues.data.0.id', $mine->id)
            );
    }
}
