<?php

namespace Tests\Feature\Api\Member;

use App\Enums\GigStatusEnum;
use App\Models\Band;
use App\Models\Gig;
use App\Models\GigMemberResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GigTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithBandAndGig(array $gigState = []): array
    {
        $user = User::factory()->create();
        $band = Band::factory()->create();
        $user->bands()->attach($band, ['role' => 'member']);

        $gig = Gig::factory()->for($band)->confirmed()->create(array_merge([
            'date' => now()->addDays(7)->format('Y-m-d'),
        ], $gigState));

        return [$user, $band, $gig];
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/gigs
    // -------------------------------------------------------------------------

    public function test_index_returns_upcoming_gigs_across_all_bands(): void
    {
        $user = User::factory()->create();

        $band1 = Band::factory()->create();
        $band2 = Band::factory()->create();
        $user->bands()->attach($band1, ['role' => 'member']);
        $user->bands()->attach($band2, ['role' => 'member']);

        Gig::factory()->for($band1)->confirmed()->create(['date' => now()->addDays(3)->format('Y-m-d')]);
        Gig::factory()->for($band2)->confirmed()->create(['date' => now()->addDays(5)->format('Y-m-d')]);

        $this->withToken($user->createToken('test')->plainTextToken)
            ->getJson('/api/v1/gigs')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'name', 'status', 'date', 'band', 'venue_name']]]);
    }

    public function test_index_excludes_past_gigs(): void
    {
        [$user, $band, $gig] = $this->memberWithBandAndGig(['date' => now()->subDay()->format('Y-m-d')]);

        $this->withToken($user->createToken('test')->plainTextToken)
            ->getJson('/api/v1/gigs')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_index_excludes_cancelled_gigs(): void
    {
        [$user, $band, $gig] = $this->memberWithBandAndGig(['status' => GigStatusEnum::Cancelled]);

        $this->withToken($user->createToken('test')->plainTextToken)
            ->getJson('/api/v1/gigs')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_index_excludes_gigs_from_other_bands(): void
    {
        $user = User::factory()->create();
        $myBand = Band::factory()->create();
        $otherBand = Band::factory()->create();
        $user->bands()->attach($myBand, ['role' => 'member']);

        Gig::factory()->for($myBand)->confirmed()->create(['date' => now()->addDays(3)->format('Y-m-d')]);
        Gig::factory()->for($otherBand)->confirmed()->create(['date' => now()->addDays(3)->format('Y-m-d')]);

        $this->withToken($user->createToken('test')->plainTextToken)
            ->getJson('/api/v1/gigs')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/v1/gigs')->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/gigs/{id}
    // -------------------------------------------------------------------------

    public function test_show_returns_gig_detail_with_rsvp(): void
    {
        [$user, $band, $gig] = $this->memberWithBandAndGig(['status' => GigStatusEnum::Pending]);

        $response = GigMemberResponse::factory()->create([
            'gig_id'  => $gig->id,
            'user_id' => $user->id,
            'status'  => 'pending',
        ]);

        $this->withToken($user->createToken('test')->plainTextToken)
            ->getJson("/api/v1/gigs/{$gig->id}")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'status', 'date', 'start_time',
                    'load_in_time', 'soundcheck_time', 'doors_time', 'end_time',
                    'fee', 'currency', 'notes', 'band', 'venue',
                    'rsvp' => ['status', 'note', 'responded_at', 'open'],
                ],
            ])
            ->assertJsonPath('data.rsvp.status', 'pending')
            ->assertJsonPath('data.rsvp.open', true);
    }

    public function test_show_returns_null_rsvp_for_auto_mode_gig(): void
    {
        [$user, $band, $gig] = $this->memberWithBandAndGig();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->getJson("/api/v1/gigs/{$gig->id}")
            ->assertOk()
            ->assertJsonPath('data.rsvp', null);
    }

    public function test_show_denies_member_of_another_band(): void
    {
        $outsider = User::factory()->create();
        $outsider->bands()->attach(Band::factory()->create(), ['role' => 'member']);

        [, , $gig] = $this->memberWithBandAndGig();

        $this->withToken($outsider->createToken('test')->plainTextToken)
            ->getJson("/api/v1/gigs/{$gig->id}")
            ->assertForbidden();
    }

    public function test_show_requires_authentication(): void
    {
        [, , $gig] = $this->memberWithBandAndGig();

        $this->getJson("/api/v1/gigs/{$gig->id}")->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/gigs/{id}/rsvp
    // -------------------------------------------------------------------------

    public function test_rsvp_records_available_response(): void
    {
        [$user, $band, $gig] = $this->memberWithBandAndGig(['status' => GigStatusEnum::Pending]);

        GigMemberResponse::factory()->create([
            'gig_id'  => $gig->id,
            'user_id' => $user->id,
            'status'  => 'pending',
        ]);

        $this->withToken($user->createToken('test')->plainTextToken)
            ->postJson("/api/v1/gigs/{$gig->id}/rsvp", [
                'available' => true,
                'note'      => 'Looking forward to it!',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'available')
            ->assertJsonPath('data.note', 'Looking forward to it!');
    }

    public function test_rsvp_records_unavailable_response(): void
    {
        [$user, $band, $gig] = $this->memberWithBandAndGig(['status' => GigStatusEnum::Pending]);

        GigMemberResponse::factory()->create([
            'gig_id'  => $gig->id,
            'user_id' => $user->id,
            'status'  => 'pending',
        ]);

        $this->withToken($user->createToken('test')->plainTextToken)
            ->postJson("/api/v1/gigs/{$gig->id}/rsvp", ['available' => false])
            ->assertOk()
            ->assertJsonPath('data.status', 'unavailable');
    }

    public function test_rsvp_rejects_when_poll_closed(): void
    {
        [$user, $band, $gig] = $this->memberWithBandAndGig(['status' => GigStatusEnum::Confirmed]);

        GigMemberResponse::factory()->create([
            'gig_id'  => $gig->id,
            'user_id' => $user->id,
        ]);

        $this->withToken($user->createToken('test')->plainTextToken)
            ->postJson("/api/v1/gigs/{$gig->id}/rsvp", ['available' => true])
            ->assertUnprocessable();
    }

    public function test_rsvp_requires_available_field(): void
    {
        [$user, $band, $gig] = $this->memberWithBandAndGig(['status' => GigStatusEnum::Pending]);

        GigMemberResponse::factory()->create([
            'gig_id'  => $gig->id,
            'user_id' => $user->id,
        ]);

        $this->withToken($user->createToken('test')->plainTextToken)
            ->postJson("/api/v1/gigs/{$gig->id}/rsvp", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('available');
    }

    public function test_rsvp_denies_member_of_another_band(): void
    {
        $outsider = User::factory()->create();
        $outsider->bands()->attach(Band::factory()->create(), ['role' => 'member']);

        [$user, $band, $gig] = $this->memberWithBandAndGig(['status' => GigStatusEnum::Pending]);

        $this->withToken($outsider->createToken('test')->plainTextToken)
            ->postJson("/api/v1/gigs/{$gig->id}/rsvp", ['available' => true])
            ->assertForbidden();
    }

    public function test_rsvp_requires_authentication(): void
    {
        [, , $gig] = $this->memberWithBandAndGig();

        $this->postJson("/api/v1/gigs/{$gig->id}/rsvp", ['available' => true])
            ->assertUnauthorized();
    }
}
