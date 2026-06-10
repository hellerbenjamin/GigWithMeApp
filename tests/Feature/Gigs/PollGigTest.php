<?php

namespace Tests\Feature\Gigs;

use App\Models\Band;
use App\Models\Gig;
use App\Models\GigMemberResponse;
use App\Models\User;
use App\Notifications\GigConfirmed;
use App\Notifications\GigPollNeedsAttention;
use App\Notifications\GigPollOpened;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PollGigTest extends TestCase
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

    public function test_booking_a_poll_gig_seeds_responses_and_asks_pending_members(): void
    {
        $band = $this->band();
        $owner = $this->member($band, 'owner');
        $alice = $this->member($band);
        $bob = $this->member($band);

        $gig = $this->bookPoll($owner);

        $this->assertSame('pending', $gig->status->value);
        $this->assertDatabaseCount('gig_member_responses', 3);

        // The creator is auto-marked available; everyone else starts pending.
        $this->assertSame('available', $gig->memberResponses()->where('user_id', $owner->id)->sole()->status->value);
        $this->assertSame('pending', $gig->memberResponses()->where('user_id', $alice->id)->sole()->status->value);

        // Only the pending members are pinged — not the creator.
        Notification::assertSentTo($alice, GigPollOpened::class);
        Notification::assertSentTo($bob, GigPollOpened::class);
        Notification::assertNotSentTo($owner, GigPollOpened::class);
    }

    public function test_poll_reaches_members_over_both_sms_and_email(): void
    {
        $band = $this->band();
        $owner = $this->member($band, 'owner');
        $both = $this->member($band); // has a phone and an email

        // A member with an email but no phone — previously skipped entirely.
        $emailOnly = User::factory()->create(['phone_number' => null]);
        $emailOnly->bands()->attach($band, ['role' => 'member']);

        $this->bookPoll($owner);

        // The member with both contact methods is asked over SMS and email.
        Notification::assertSentTo(
            $both,
            GigPollOpened::class,
            fn ($notification, array $channels) => in_array('vonage', $channels, true)
                && in_array('mail', $channels, true),
        );

        // The phone-less member is still reached — over email alone.
        Notification::assertSentTo(
            $emailOnly,
            GigPollOpened::class,
            fn ($notification, array $channels) => $channels === ['mail'],
        );
    }

    public function test_poll_opened_email_renders_with_subject_and_rsvp_link(): void
    {
        $band = $this->band();
        $owner = $this->member($band, 'owner');
        $alice = $this->member($band);

        $gig = $this->bookPoll($owner);
        $response = $gig->memberResponses()->where('user_id', $alice->id)->sole();

        $mail = (new GigPollOpened($response))->toMail($alice);

        $this->assertStringContainsString($band->name, $mail->subject);
        $this->assertSame(route('rsvp.show', $response->token), $mail->actionUrl);
    }

    public function test_everyone_available_confirms_the_gig(): void
    {
        $band = $this->band();
        $owner = $this->member($band, 'owner');
        $alice = $this->member($band);

        $gig = $this->bookPoll($owner);
        $token = $gig->memberResponses()->where('user_id', $alice->id)->sole()->token;

        // Alice replies "available" through her magic link (no login).
        $this->post("/rsvp/{$token}", ['available' => true])->assertSessionHas('success');

        $this->assertSame('confirmed', $gig->fresh()->status->value);
        Notification::assertSentTo($owner, GigConfirmed::class);
        Notification::assertSentTo($alice, GigConfirmed::class);
    }

    public function test_an_unavailable_reply_notifies_admins_once_and_keeps_pending(): void
    {
        $band = $this->band();
        $owner = $this->member($band, 'owner');
        $alice = $this->member($band);

        $gig = $this->bookPoll($owner);
        $token = $gig->memberResponses()->where('user_id', $alice->id)->sole()->token;

        $this->post("/rsvp/{$token}", ['available' => false, 'note' => 'out of town'])
            ->assertSessionHas('success');

        $gig->refresh();
        $this->assertSame('pending', $gig->status->value);
        $this->assertNotNull($gig->poll_closed_at);

        // The owner (admin-level) is asked to decide — exactly once.
        Notification::assertSentToTimes($owner, GigPollNeedsAttention::class, 1);
        Notification::assertNotSentTo($alice, GigPollNeedsAttention::class);
    }

    public function test_rsvp_page_renders_for_a_valid_token(): void
    {
        $band = $this->band();
        $owner = $this->member($band, 'owner');
        $alice = $this->member($band);

        $gig = $this->bookPoll($owner);
        $token = $gig->memberResponses()->where('user_id', $alice->id)->sole()->token;

        $this->get("/rsvp/{$token}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Rsvp/Show')
                ->where('memberName', $alice->name)
                ->where('bandName', $band->name)
                ->where('closed', false)
            );
    }

    public function test_a_reply_is_ignored_once_the_gig_is_settled(): void
    {
        $band = $this->band();
        $owner = $this->member($band, 'owner');
        $alice = $this->member($band);

        $gig = $this->bookPoll($owner);
        $token = $gig->memberResponses()->where('user_id', $alice->id)->sole()->token;

        // Alice's "available" confirms the gig (solo pending member).
        $this->post("/rsvp/{$token}", ['available' => true]);
        $this->assertSame('confirmed', $gig->fresh()->status->value);

        // A later change of heart can't reopen a settled gig.
        $this->post("/rsvp/{$token}", ['available' => false])->assertSessionHas('info');
        $this->assertSame('confirmed', $gig->fresh()->status->value);
        $this->assertSame('available', GigMemberResponse::where('token', $token)->sole()->status->value);
    }

    public function test_an_unknown_token_404s(): void
    {
        $this->get('/rsvp/nope-not-a-real-token')->assertNotFound();
    }
}
