<?php

namespace Tests\Feature\Bands;

use App\Models\Band;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CreateBandTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_page_renders_with_genre_suggestions(): void
    {
        Genre::create(['name' => 'Synthwave', 'slug' => 'synthwave']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/bands/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bands/Create')
                ->where('genreSuggestions', ['Synthwave'])
            );
    }

    public function test_user_can_create_a_band_and_becomes_its_active_owner(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/bands', [
            'name' => 'The Velvet Hours',
            'hometown' => 'Portland, OR',
            'founded_year' => 2021,
            'website' => 'https://velvethours.test',
            'email' => 'hello@velvethours.test',
            'description' => 'Indie dream pop.',
        ]);

        $band = Band::where('name', 'The Velvet Hours')->firstOrFail();

        $response->assertRedirect('/dashboard')
            ->assertSessionHas('success')
            ->assertSessionHas('active_band_id', $band->id);

        // Creator is the owner.
        $this->assertDatabaseHas('band_user', [
            'band_id' => $band->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        $this->assertSame('the-velvet-hours', $band->slug);
        $this->assertSame('Portland, OR', $band->hometown);
        $this->assertSame(2021, $band->founded_year);
    }

    public function test_name_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/bands', ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('bands', 0);
    }

    public function test_genres_are_created_and_attached_collapsing_by_slug(): void
    {
        Genre::create(['name' => 'Indie', 'slug' => 'indie']);
        $user = User::factory()->create();

        $this->actingAs($user)->post('/bands', [
            'name' => 'Neon Saturday',
            // "indie" matches the existing genre by slug; "Synthwave" is new.
            'genres' => ['indie', 'Synthwave', 'Synthwave'],
        ]);

        $band = Band::where('name', 'Neon Saturday')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            ['Indie', 'Synthwave'],
            $band->genres->pluck('name')->all(),
        );
        // No duplicate "Synthwave" genre was created.
        $this->assertSame(1, Genre::where('slug', 'synthwave')->count());
    }

    public function test_slugs_are_made_unique(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/bands', ['name' => 'Echo']);
        $this->actingAs($user)->post('/bands', ['name' => 'Echo']);

        $this->assertEqualsCanonicalizing(
            ['echo', 'echo-2'],
            Band::where('name', 'Echo')->pluck('slug')->all(),
        );
    }

    public function test_guests_cannot_reach_band_creation(): void
    {
        $this->get('/bands/create')->assertRedirect('/login');
        $this->post('/bands', ['name' => 'Ghosts'])->assertRedirect('/login');
        $this->assertDatabaseCount('bands', 0);
    }
}
