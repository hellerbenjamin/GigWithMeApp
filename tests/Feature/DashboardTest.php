<?php

namespace Tests\Feature;

use App\Models\Band;
use App\Models\Gig;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Band}
     */
    private function userInBand(): array
    {
        $user = User::factory()->create();
        $band = Band::factory()->create(['default_currency' => 'USD']);
        $user->bands()->attach($band, ['role' => 'owner']);

        return [$user, $band];
    }

    public function test_dashboard_counts_only_the_active_bands_resources(): void
    {
        [$user, $band] = $this->userInBand();

        // A second member of the band — owner + this one = 2.
        $band->users()->attach(User::factory()->create(), ['role' => 'member']);

        $venues = Venue::factory()->count(3)->for($band)->create();
        // Reuse an existing venue so the gig factory doesn't spawn extra ones and
        // throw off the venue count.
        $venue = $venues->first()->id;

        // Two gigs ahead, one in the past, one cancelled — only the two future,
        // non-cancelled gigs count toward "upcoming".
        Gig::factory()->for($band)->confirmed()->create(['venue_id' => $venue, 'date' => Carbon::tomorrow()->toDateString()]);
        Gig::factory()->for($band)->create(['venue_id' => $venue, 'status' => 'pending', 'date' => Carbon::today()->addDays(5)->toDateString()]);
        Gig::factory()->for($band)->confirmed()->create(['venue_id' => $venue, 'date' => Carbon::yesterday()->toDateString()]);
        Gig::factory()->for($band)->cancelled()->create(['venue_id' => $venue, 'date' => Carbon::tomorrow()->toDateString()]);

        // Another band's data must not bleed in.
        $other = Band::factory()->create();
        Venue::factory()->count(2)->for($other)->create();
        Gig::factory()->for($other)->confirmed()->create(['date' => Carbon::tomorrow()->toDateString()]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('stats.upcomingGigs', 2)
                ->where('stats.venues', 3)
                ->where('stats.members', 2)
                ->where('stats.currency', 'USD')
                ->has('upcomingGigs', 2)
            );
    }

    public function test_booked_this_month_sums_confirmed_fees_in_the_current_month(): void
    {
        [$user, $band] = $this->userInBand();

        $inMonth = Carbon::now()->startOfMonth()->addDays(2)->toDateString();
        $nextMonth = Carbon::now()->addMonth()->startOfMonth()->toDateString();

        Gig::factory()->for($band)->confirmed()->create(['date' => $inMonth, 'fee' => 1000]);
        Gig::factory()->for($band)->confirmed()->create(['date' => $inMonth, 'fee' => 500]);
        // Pending this month and confirmed next month are both excluded.
        Gig::factory()->for($band)->create(['status' => 'pending', 'date' => $inMonth, 'fee' => 999]);
        Gig::factory()->for($band)->confirmed()->create(['date' => $nextMonth, 'fee' => 999]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                // json_encode renders the whole-dollar float as an int.
                ->where('stats.bookedThisMonth', 1500)
            );
    }

    public function test_upcoming_gigs_are_ordered_soonest_first_and_capped(): void
    {
        [$user, $band] = $this->userInBand();

        foreach ([10, 2, 6, 1, 8, 4, 12] as $offset) {
            Gig::factory()->for($band)->confirmed()->create([
                'date' => Carbon::today()->addDays($offset)->toDateString(),
            ]);
        }

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('upcomingGigs', 5)
                ->where('upcomingGigs.0.date', Carbon::today()->addDay()->toDateString())
                ->where('upcomingGigs.4.date', Carbon::today()->addDays(8)->toDateString())
            );
    }

    public function test_member_role_sees_member_dashboard(): void
    {
        $user = User::factory()->create();
        $band = Band::factory()->create();
        $user->bands()->attach($band, ['role' => 'member']);
        Gig::factory()->for($band)->confirmed()->create([
            'date' => \Illuminate\Support\Carbon::tomorrow()->toDateString(),
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('MemberDashboard')
                ->where('stats.upcomingGigs', 1)
                ->has('upcomingGigs', 1)
                ->missing('stats.bookedThisMonth')
                ->missing('stats.venues')
                ->missing('stats.members')
            );
    }

    public function test_member_dashboard_includes_rsvp_status(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $band = Band::factory()->create();
        $owner->bands()->attach($band, ['role' => 'owner']);
        $member->bands()->attach($band, ['role' => 'member']);

        $gig = Gig::factory()->for($band)->create([
            'status' => 'pending',
            'booking_mode' => 'poll',
            'date' => \Illuminate\Support\Carbon::tomorrow()->toDateString(),
        ]);

        // Seed a poll response for the member.
        $gig->memberResponses()->create([
            'user_id' => $member->id,
            'status' => 'available',
            'responded_at' => now(),
            'channel' => 'web',
            'token' => \Illuminate\Support\Str::random(40),
            'critical' => true,
        ]);

        $this->actingAs($member)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('MemberDashboard')
                ->where('upcomingGigs.0.myRsvp', 'available')
                ->where('upcomingGigs.0.myRsvpLabel', 'Available')
            );
    }
}
