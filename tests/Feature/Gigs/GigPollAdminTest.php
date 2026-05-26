<?php

namespace Tests\Feature\Gigs;

use App\Models\Band;
use App\Models\Gig;
use App\Models\User;
use App\Notifications\GigConfirmed;
use App\Notifications\GigPollOpened;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The in-app admin surface for poll-mode gigs: the detail page's poll progress,
 * plus the owner/admin "confirm anyway" and "re-poll" actions on a poll that
 * closed needing attention. See docs/gig-booking-flow.md.
 */
class GigPollAdminTest extends TestCase
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

    private function member(Band $band, string $role = 'member'): User
    {
        $user = User::factory()->create([
            'phone_number' => '+1555'.fake()->unique()->numerify('0######'),
        ]);
        $user->bands()->attach($band, ['role' => $role]);

        return $user;
    }

    private function bookPoll(User $actor): Gig
    {
        $this->actingAs($actor)->post('/gigs', [
            'type' => 'gig',
            'booking_mode' => 'poll',
            'date' => '2026-08-15',
            'currency' => 'USD',
        ])->assertRedirect('/gigs');

        return Gig::sole();
    }

    /**
     * Drive a poll to the needs-attention state: everyone's replied, but one
     * member can't make it, so it closes pending the admin's call. The declining
     * member must exist before the poll opens, or a solo band would auto-confirm.
     *
     * @return array{0: Gig, 1: User} the gig and the member who declined
     */
    private function pollNeedingAttention(Band $band, User $owner): array
    {
        $declined = $this->member($band);

        $gig = $this->bookPoll($owner);

        $response = $gig->memberResponses()->where('user_id', $declined->id)->sole();
        $this->post("/rsvp/{$response->token}", ['available' => false, 'note' => 'on tour']);

        return [$gig->fresh(), $declined];
    }

    public function test_show_page_renders_poll_progress_for_owner(): void
    {
        $band = $this->band();
        $owner = $this->member($band, 'owner');
        $this->member($band);

        $gig = $this->bookPoll($owner);

        $this->actingAs($owner)->get("/gigs/{$gig->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Gigs/Show')
                ->where('canManage', true)
                ->where('poll.total', 2)
                ->where('poll.availableCount', 1)   // the owner, auto-available
                ->where('poll.closed', false)
                ->has('poll.members', 2)
            );
    }

    public function test_auto_mode_gig_has_no_poll_block(): void
    {
        $band = $this->band();
        $owner = $this->member($band, 'owner');

        $gig = Gig::factory()->for($band)->create(['booking_mode' => 'auto']);

        $this->actingAs($owner)->get("/gigs/{$gig->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Gigs/Show')
                ->where('poll', null)
            );
    }

    public function test_admin_can_confirm_a_poll_that_needs_attention(): void
    {
        $band = $this->band();
        $owner = $this->member($band, 'owner');

        [$gig, $declined] = $this->pollNeedingAttention($band, $owner);
        $this->assertSame('pending', $gig->status->value);
        $this->assertNotNull($gig->poll_closed_at);

        $this->actingAs($owner)->post("/gigs/{$gig->id}/confirm")
            ->assertRedirect("/gigs/{$gig->id}")
            ->assertSessionHas('success');

        $this->assertSame('confirmed', $gig->fresh()->status->value);
        Notification::assertSentTo($owner, GigConfirmed::class);
        Notification::assertSentTo($declined, GigConfirmed::class);
    }

    public function test_re_poll_resets_replies_and_asks_pending_members_again(): void
    {
        $band = $this->band();
        $owner = $this->member($band, 'owner');

        [$gig, $declined] = $this->pollNeedingAttention($band, $owner);

        $this->actingAs($owner)->post("/gigs/{$gig->id}/repoll")
            ->assertRedirect("/gigs/{$gig->id}")
            ->assertSessionHas('success');

        $gig->refresh();
        $this->assertSame('pending', $gig->status->value);
        $this->assertNull($gig->poll_closed_at);

        // The declining member is back to pending and asked again; the owner who
        // re-polled is auto-marked available, exactly as on a fresh open.
        $this->assertSame('pending', $gig->memberResponses()->where('user_id', $declined->id)->sole()->status->value);
        $this->assertSame('available', $gig->memberResponses()->where('user_id', $owner->id)->sole()->status->value);
        Notification::assertSentTo($declined, GigPollOpened::class);
    }

    public function test_a_plain_member_cannot_confirm_or_re_poll(): void
    {
        $band = $this->band();
        $owner = $this->member($band, 'owner');
        $regular = $this->member($band);

        [$gig] = $this->pollNeedingAttention($band, $owner);

        $this->actingAs($regular)->post("/gigs/{$gig->id}/confirm")->assertForbidden();
        $this->actingAs($regular)->post("/gigs/{$gig->id}/repoll")->assertForbidden();

        $this->assertSame('pending', $gig->fresh()->status->value);
    }

    public function test_another_bands_gig_is_not_reachable(): void
    {
        $band = $this->band();
        $owner = $this->member($band, 'owner');

        // A gig that belongs to a band the owner has nothing to do with.
        $otherGig = Gig::factory()->for($this->band())->create(['booking_mode' => 'poll']);

        $this->actingAs($owner)->get("/gigs/{$otherGig->id}")->assertNotFound();
        $this->actingAs($owner)->post("/gigs/{$otherGig->id}/confirm")->assertNotFound();
        $this->actingAs($owner)->post("/gigs/{$otherGig->id}/repoll")->assertNotFound();
    }
}
