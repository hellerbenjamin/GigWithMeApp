<?php

namespace Tests\Feature\Bands;

use App\Models\Band;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UpdateBandSettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A user attached to a fresh band in the given role, with that band active.
     *
     * @return array{0: User, 1: Band}
     */
    private function userInBand(string $role = 'owner'): array
    {
        $user = User::factory()->create();
        $band = Band::factory()->create();
        $user->bands()->attach($band, ['role' => $role]);

        return [$user, $band];
    }

    public function test_settings_page_renders_with_the_active_band(): void
    {
        [$owner, $band] = $this->userInBand('admin');
        $band->genres()->attach(Genre::create(['name' => 'Dream Pop', 'slug' => 'dream-pop']));

        $this->actingAs($owner)
            ->get('/settings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Index')
                ->where('band.name', $band->name)
                ->where('band.genres', ['Dream Pop'])
                ->where('canManage', true)
                ->where('band.defaultBookingMode', 'auto')
                ->has('genreSuggestions')
                ->has('bookingModes')
            );
    }

    public function test_an_owner_can_update_settings(): void
    {
        [$owner, $band] = $this->userInBand('owner');

        $this->actingAs($owner)->put('/settings', [
            'name' => 'The Velvet Hours',
            'genres' => ['Indie', 'Dream Pop'],
            'hometown' => 'Portland, OR',
            'founded_year' => 2019,
            'website' => 'https://velvethours.test',
            'email' => 'hello@velvethours.test',
            'description' => 'A moody five-piece.',
            'default_currency' => 'EUR',
            'default_booking_mode' => 'poll',
        ])->assertRedirect('/settings')->assertSessionHas('success');

        $band->refresh();
        $this->assertSame('The Velvet Hours', $band->name);
        $this->assertSame('Portland, OR', $band->hometown);
        $this->assertSame(2019, $band->founded_year);
        $this->assertSame('EUR', $band->default_currency);
        $this->assertSame('poll', $band->default_booking_mode);
        $this->assertEqualsCanonicalizing(
            ['Indie', 'Dream Pop'],
            $band->genres()->pluck('name')->all(),
        );
    }

    public function test_the_slug_stays_put_on_rename(): void
    {
        [$owner, $band] = $this->userInBand('owner');
        $originalSlug = $band->slug;

        $this->actingAs($owner)->put('/settings', [
            'name' => 'A Completely Different Name',
            'default_currency' => 'USD',
            'default_booking_mode' => 'auto',
        ])->assertRedirect('/settings');

        $this->assertSame($originalSlug, $band->fresh()->slug);
    }

    public function test_blank_optionals_are_stored_as_null(): void
    {
        [$owner, $band] = $this->userInBand('owner');
        $band->update(['hometown' => 'Somewhere', 'website' => 'https://old.test']);

        $this->actingAs($owner)->put('/settings', [
            'name' => $band->name,
            'hometown' => null,
            'website' => null,
            'default_currency' => 'USD',
            'default_booking_mode' => 'auto',
        ])->assertRedirect('/settings');

        $band->refresh();
        $this->assertNull($band->hometown);
        $this->assertNull($band->website);
    }

    public function test_genres_can_be_cleared(): void
    {
        [$owner, $band] = $this->userInBand('owner');
        $band->genres()->attach(Genre::create(['name' => 'Jazz', 'slug' => 'jazz']));

        $this->actingAs($owner)->put('/settings', [
            'name' => $band->name,
            'genres' => [],
            'default_currency' => 'USD',
            'default_booking_mode' => 'auto',
        ])->assertRedirect('/settings');

        $this->assertCount(0, $band->fresh()->genres);
    }

    public function test_name_is_required(): void
    {
        [$owner] = $this->userInBand('owner');

        $this->actingAs($owner)->put('/settings', [
            'name' => '',
            'default_currency' => 'USD',
        ])->assertSessionHasErrors('name');
    }

    public function test_members_can_view_but_not_edit(): void
    {
        [$member, $band] = $this->userInBand('member');

        $this->actingAs($member)
            ->get('/settings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('canManage', false));

        $this->actingAs($member)->put('/settings', [
            'name' => 'Hijacked',
            'default_currency' => 'USD',
        ])->assertForbidden();

        $this->assertNotSame('Hijacked', $band->fresh()->name);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/settings')->assertRedirect('/login');
        $this->put('/settings', [])->assertRedirect('/login');
    }
}
