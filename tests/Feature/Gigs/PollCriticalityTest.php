<?php

namespace Tests\Feature\Gigs;

use App\Models\Band;
use App\Models\Gig;
use App\Models\User;
use App\Notifications\GigConfirmed;
use App\Notifications\GigPollNeedsAttention;
use App\Services\GigBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PollCriticalityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function band(): Band
    {
        return Band::factory()->create();
    }

    private function member(Band $band, string $role = 'member', bool $critical = true): User
    {
        $user = User::factory()->create();
        $user->bands()->attach($band, ['role' => $role, 'critical' => $critical]);

        return $user;
    }

    private function openPoll(Band $band, User $actor): Gig
    {
        $gig = Gig::factory()->for($band)->create([
            'status' => 'pending',
            'booking_mode' => 'poll',
            'date' => '2026-08-15',
        ]);

        app(GigBookingService::class)->openPoll($gig, $actor);
        $gig->refresh();

        return $gig;
    }

    private function respond(Gig $gig, User $user, string $status): void
    {
        $response = $gig->memberResponses()->where('user_id', $user->id)->sole();
        app(GigBookingService::class)->recordResponse($response, \App\Enums\GigResponseStatusEnum::from($status), 'web');
    }

    // -----------------------------------------------------------------

    public function test_critical_field_is_snapshotted_when_poll_opens(): void
    {
        $band = $this->band();
        $critical = $this->member($band, 'member', true);
        $nonCritical = $this->member($band, 'owner', false);

        $gig = $this->openPoll($band, $nonCritical);

        $criticalRow = $gig->memberResponses()->where('user_id', $critical->id)->sole();
        $nonCriticalRow = $gig->memberResponses()->where('user_id', $nonCritical->id)->sole();

        $this->assertTrue($criticalRow->critical);
        $this->assertFalse($nonCriticalRow->critical);
    }

    public function test_auto_confirms_when_all_critical_members_available_even_if_noncritical_cannot(): void
    {
        $band = $this->band();
        $owner = $this->member($band, 'owner', true);   // critical
        $drummer = $this->member($band, 'member', true); // critical
        $roadie = $this->member($band, 'member', false); // not critical

        $gig = $this->openPoll($band, $owner);

        $this->respond($gig, $drummer, 'available');
        $this->respond($gig, $roadie, 'unavailable'); // non-critical says no

        $gig->refresh();
        $this->assertEquals('confirmed', $gig->status->value);
        Notification::assertSentTo([$owner, $drummer, $roadie], GigConfirmed::class);
        Notification::assertNotSentTo([$owner, $drummer, $roadie], GigPollNeedsAttention::class);
    }

    public function test_notifies_admins_when_critical_member_is_unavailable(): void
    {
        $band = $this->band();
        $owner = $this->member($band, 'owner', true);    // critical
        $guitarist = $this->member($band, 'member', true); // critical
        $roadie = $this->member($band, 'member', false);   // not critical

        $gig = $this->openPoll($band, $owner);

        $this->respond($gig, $guitarist, 'unavailable'); // critical says no
        $this->respond($gig, $roadie, 'available');

        $gig->refresh();
        $this->assertEquals('pending', $gig->status->value);
        $this->assertNotNull($gig->poll_closed_at);
        Notification::assertSentTo($owner, GigPollNeedsAttention::class);
    }

    public function test_fallback_requires_all_when_no_critical_members(): void
    {
        $band = $this->band();
        $owner = $this->member($band, 'owner', false);  // not critical
        $member = $this->member($band, 'member', false); // not critical

        $gig = $this->openPoll($band, $owner);

        // With no critical members, behaves like old logic: need everyone
        $this->respond($gig, $member, 'unavailable');

        $gig->refresh();
        $this->assertEquals('pending', $gig->status->value);
        $this->assertNotNull($gig->poll_closed_at);
    }

    public function test_fallback_confirms_when_all_noncritical_available(): void
    {
        $band = $this->band();
        $owner = $this->member($band, 'owner', false);  // not critical
        $member = $this->member($band, 'member', false); // not critical

        $gig = $this->openPoll($band, $owner);
        $this->respond($gig, $member, 'available');

        $gig->refresh();
        $this->assertEquals('confirmed', $gig->status->value);
    }

    public function test_repoll_refreshes_critical_snapshot(): void
    {
        $band = $this->band();
        $owner = $this->member($band, 'owner', true);
        $member = $this->member($band, 'member', true);

        $gig = $this->openPoll($band, $owner);

        // Change the member's criticality after the poll was opened
        $band->users()->updateExistingPivot($member->id, ['critical' => false]);

        // Re-poll should snapshot the new value
        app(GigBookingService::class)->rePoll($gig->fresh(), $owner);

        $gig->refresh();
        $row = $gig->memberResponses()->where('user_id', $member->id)->sole();
        $this->assertFalse($row->critical);
    }
}
