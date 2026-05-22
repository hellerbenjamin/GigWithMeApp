<?php

namespace Database\Seeders;

use App\Enums\BandUserRoleEnum;
use App\Models\Band;
use App\Models\Gig;
use App\Models\Genre;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Run on a clean database with:  ddev artisan migrate:fresh --seed
     */
    public function run(): void
    {
        // ─────────────────────────────────────────────────────────────
        //  TEST LOGIN
        //  email:    test@roadie.test
        //  password: password
        //  Member of all three demo bands below (one of each role) so the
        //  band switcher and role chips have something to show.
        // ─────────────────────────────────────────────────────────────
        $user = User::factory()->create([
            'name' => 'Casey Rivera',
            'email' => 'test@roadie.test',
            'password' => 'password', // hashed by the User model's cast
        ]);

        // A small pool of other musicians to fill out band rosters / counts.
        $bandmates = User::factory()->count(6)->create();

        $bands = [
            ['name' => 'The Velvet Hours', 'role' => BandUserRoleEnum::Owner, 'genres' => ['Indie', 'Dream Pop']],
            ['name' => 'Neon Saturday', 'role' => BandUserRoleEnum::Admin, 'genres' => ['Synthwave']],
            ['name' => 'Open Mic Collective', 'role' => BandUserRoleEnum::Member, 'genres' => ['Acoustic']],
        ];

        foreach ($bands as $spec) {
            $band = Band::factory()->create(['name' => $spec['name']]);

            // The test user, with the role this band is meant to demo.
            $user->bands()->attach($band, ['role' => $spec['role']->value]);

            // 2–3 other members; the first is an admin, the rest plain members.
            $members = $bandmates->random(random_int(2, 3))->values();
            foreach ($members as $i => $mate) {
                $band->users()->attach($mate, [
                    'role' => ($i === 0 ? BandUserRoleEnum::Admin : BandUserRoleEnum::Member)->value,
                ]);
            }

            $genres = collect($spec['genres'])->map(fn (string $name) => Genre::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            ));
            $band->genres()->sync($genres->pluck('id'));

            // Venues this band has played / can book.
            $venues = Venue::factory()->count(3)->create(['band_id' => $band->id]);

            // Past gigs — mostly settled, so history looks real.
            Gig::factory()->count(4)->confirmed()->create([
                'band_id' => $band->id,
                'venue_id' => fn () => $venues->random()->id,
                'date' => fn () => fake()->dateTimeBetween('-4 months', '-1 week')->format('Y-m-d'),
            ]);

            // Upcoming gigs — mixed statuses (the factory's default spread).
            Gig::factory()->count(5)->create([
                'band_id' => $band->id,
                'venue_id' => fn () => $venues->random()->id,
                'date' => fn () => fake()->dateTimeBetween('+3 days', '+5 months')->format('Y-m-d'),
            ]);

            // One upcoming rehearsal, to exercise the non-gig event type.
            Gig::factory()->rehearsal()->create([
                'band_id' => $band->id,
                'venue_id' => $venues->first()->id,
                'date' => fake()->dateTimeBetween('now', '+2 weeks')->format('Y-m-d'),
            ]);
        }

        $this->command->info('Seeded test login → test@roadie.test / password');
    }
}
