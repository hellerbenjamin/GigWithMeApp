<?php

namespace Tests\Feature\Invites;

use App\Models\Band;
use App\Models\Gig;
use App\Models\User;
use App\Notifications\GigConfirmed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\TestCase;

class AcceptInviteTest extends TestCase
{
    use RefreshDatabase;

    private function invitedMember(string $band = 'The Night Owls'): User
    {
        $user = User::factory()->invited()->create();
        $user->bands()->attach(Band::factory()->create(['name' => $band]), ['role' => 'member']);

        return $user;
    }

    public function test_acceptance_page_renders_for_a_valid_token(): void
    {
        $user = $this->invitedMember();

        $this->get("/invite/{$user->invite_token}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Invite/Accept')
                ->where('memberName', $user->name)
                ->where('bandNames', ['The Night Owls'])
                ->missing('optInText')
                ->missing('phoneNumber')
            );
    }

    public function test_an_unknown_token_404s(): void
    {
        $this->get('/invite/nope')->assertNotFound();
    }

    public function test_acceptance_records_name_and_redirects_to_setup(): void
    {
        $user = $this->invitedMember();

        $pushToken = $user->ensurePushToken();

        $this->post("/invite/{$user->invite_token}", ['name' => 'Jordan Reyes'])
            ->assertRedirect("/invite/setup/{$pushToken}");

        $user->refresh();
        $this->assertSame('Jordan Reyes', $user->name);
        $this->assertNull($user->invite_token);
    }

    public function test_acceptance_mints_a_push_token_and_redirects_to_setup(): void
    {
        $user = $this->invitedMember();
        $this->assertNull($user->push_token);

        $response = $this->post("/invite/{$user->invite_token}", ['name' => $user->name]);

        $pushToken = $user->fresh()->push_token;
        $this->assertNotNull($pushToken);
        $response->assertRedirect("/invite/setup/{$pushToken}");
    }

    public function test_name_is_required(): void
    {
        $user = $this->invitedMember();

        $this->post("/invite/{$user->invite_token}", ['name' => ''])
            ->assertSessionHasErrors('name');

        // Token not consumed on validation failure.
        $this->assertNotNull($user->fresh()->invite_token);
    }

    public function test_setup_page_renders_for_a_valid_push_token(): void
    {
        $user = User::factory()->create();
        $pushToken = $user->ensurePushToken();

        $this->get("/invite/setup/{$pushToken}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Invite/Setup')
                ->where('pushToken', $pushToken)
            );
    }

    public function test_setup_page_404s_for_unknown_push_token(): void
    {
        $this->get('/invite/setup/nope')->assertNotFound();
    }

    public function test_push_channel_used_when_subscribed(): void
    {
        $notification = new GigConfirmed(new Gig);

        $subscriber = User::factory()->create();
        $subscriber->updatePushSubscription('https://push.example.com/sub', 'p256dh-key', 'auth-key');
        $channels = $notification->via($subscriber);

        $this->assertContains(WebPushChannel::class, $channels);
        $this->assertContains('mail', $channels);
        $this->assertNotContains('vonage', $channels);
    }

    public function test_email_is_fallback_when_no_push_subscription(): void
    {
        $notification = new GigConfirmed(new Gig);

        $user = User::factory()->create(['phone_number' => '+15551112222']);
        $channels = $notification->via($user);

        $this->assertNotContains(WebPushChannel::class, $channels);
        $this->assertNotContains('vonage', $channels);
        $this->assertContains('mail', $channels);
    }
}
