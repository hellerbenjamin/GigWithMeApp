<?php

namespace Tests\Feature\Push;

use App\Models\Band;
use App\Models\Gig;
use App\Models\User;
use App\Notifications\GigPollOpened;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\TestCase;

class WebPushTest extends TestCase
{
    use RefreshDatabase;

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

    /** A valid-looking PushSubscription payload from the browser. */
    private function subscriptionPayload(string $endpoint = 'https://push.example.com/abc'): array
    {
        return [
            'endpoint' => $endpoint,
            'keys' => [
                'p256dh' => 'BNc...publickey',
                'auth' => 'authsecret',
            ],
            'contentEncoding' => 'aes128gcm',
        ];
    }

    public function test_a_subscribed_member_gets_web_push_instead_of_sms(): void
    {
        Notification::fake();

        $band = $this->band();
        $owner = $this->member($band, 'owner');
        $alice = $this->member($band);

        // Alice has granted push — she should be reached over web push, and SMS
        // should be supplanted. Email still goes (she has an address).
        $alice->updatePushSubscription('https://push.example.com/alice', 'p256dh-key', 'auth-key');

        $this->actingAs($owner)->post('/gigs', [
            'type' => 'gig',
            'booking_mode' => 'poll',
            'date' => '2026-08-15',
            'currency' => 'USD',
        ])->assertRedirect('/gigs');

        Notification::assertSentTo(
            $alice,
            GigPollOpened::class,
            fn ($notification, array $channels) => in_array(WebPushChannel::class, $channels, true)
                && ! in_array('vonage', $channels, true)
                && in_array('mail', $channels, true),
        );
    }

    public function test_an_unsubscribed_member_still_gets_sms_and_mail(): void
    {
        Notification::fake();

        $band = $this->band();
        $owner = $this->member($band, 'owner');
        $bob = $this->member($band); // no push subscription

        $this->actingAs($owner)->post('/gigs', [
            'type' => 'gig',
            'booking_mode' => 'poll',
            'date' => '2026-08-15',
            'currency' => 'USD',
        ])->assertRedirect('/gigs');

        Notification::assertSentTo(
            $bob,
            GigPollOpened::class,
            fn ($notification, array $channels) => in_array('vonage', $channels, true)
                && in_array('mail', $channels, true)
                && ! in_array(WebPushChannel::class, $channels, true),
        );
    }

    public function test_ensure_push_token_is_idempotent(): void
    {
        $user = User::factory()->create();

        $first = $user->ensurePushToken();
        $second = $user->fresh()->ensurePushToken();

        $this->assertNotEmpty($first);
        $this->assertSame($first, $second);
    }

    public function test_a_member_can_subscribe_by_their_durable_token(): void
    {
        $band = $this->band();
        $alice = $this->member($band);
        $token = $alice->ensurePushToken();

        $this->postJson("/push/subscribe/{$token}", $this->subscriptionPayload())
            ->assertNoContent();

        $this->assertTrue($alice->fresh()->hasPushSubscription());
        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_id' => $alice->id,
            'endpoint' => 'https://push.example.com/abc',
        ]);
    }

    public function test_subscribing_with_an_unknown_token_404s(): void
    {
        $this->postJson('/push/subscribe/not-a-real-token', $this->subscriptionPayload())
            ->assertNotFound();
    }

    public function test_a_member_can_unsubscribe_by_token(): void
    {
        $band = $this->band();
        $alice = $this->member($band);
        $token = $alice->ensurePushToken();
        $alice->updatePushSubscription('https://push.example.com/abc', 'k', 'a');

        $this->deleteJson("/push/subscribe/{$token}", [
            'endpoint' => 'https://push.example.com/abc',
        ])->assertNoContent();

        $this->assertFalse($alice->fresh()->hasPushSubscription());
    }

    public function test_a_logged_in_user_subscribes_via_the_session_endpoint(): void
    {
        $band = $this->band();
        $owner = $this->member($band, 'owner');

        $this->actingAs($owner)
            ->postJson('/push/subscribe', $this->subscriptionPayload('https://push.example.com/owner'))
            ->assertNoContent();

        $this->assertTrue($owner->fresh()->hasPushSubscription());
    }

    public function test_a_push_action_records_the_reply_over_the_push_channel(): void
    {
        Notification::fake();

        $band = $this->band();
        $owner = $this->member($band, 'owner');
        $alice = $this->member($band);

        $this->actingAs($owner)->post('/gigs', [
            'type' => 'gig',
            'booking_mode' => 'poll',
            'date' => '2026-08-15',
            'currency' => 'USD',
        ]);

        $gig = Gig::sole();
        $token = $gig->memberResponses()->where('user_id', $alice->id)->sole()->token;

        // The service worker fires this from the notification's "I'm in" action.
        $this->postJson("/rsvp/{$token}/reply", ['available' => true])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $response = $gig->memberResponses()->where('user_id', $alice->id)->sole();
        $this->assertSame('available', $response->status->value);
        $this->assertSame('push', $response->channel);
    }

    public function test_member_home_renders_for_a_valid_token(): void
    {
        $band = $this->band();
        $alice = $this->member($band);
        $token = $alice->ensurePushToken();

        $this->get("/m/{$token}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Member/Home')
                ->where('memberName', $alice->name)
                ->where('pushToken', $token)
            );
    }

    public function test_member_manifest_carries_the_personal_start_url(): void
    {
        $band = $this->band();
        $alice = $this->member($band);
        $token = $alice->ensurePushToken();

        $this->get("/m/{$token}/manifest.webmanifest")
            ->assertOk()
            ->assertJson(['start_url' => "/m/{$token}"]);
    }
}
