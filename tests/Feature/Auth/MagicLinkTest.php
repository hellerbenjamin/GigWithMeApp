<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MagicLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_magic_link_page_renders(): void
    {
        $this->get('/login/link')->assertOk()->assertInertia(
            fn ($page) => $page->component('Auth/MagicLink')
        );
    }

    public function test_sending_to_a_registered_email_dispatches_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'jordan@example.com']);

        $this->post('/login/link', ['email' => 'jordan@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, MagicLinkNotification::class);
        $this->assertNotNull($user->fresh()->magic_link_token);
    }

    public function test_sending_to_an_unknown_email_shows_success_but_sends_nothing(): void
    {
        Notification::fake();

        $this->post('/login/link', ['email' => 'ghost@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertNothingSent();
    }

    public function test_a_valid_token_signs_the_user_in(): void
    {
        $user = User::factory()->create();
        $token = $user->generateMagicToken();

        $this->get("/login/link/{$token}")
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->fresh()->magic_link_token);
    }

    public function test_an_expired_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $user->generateMagicToken();
        $user->forceFill(['magic_link_expires_at' => now()->subMinute()])->save();

        $this->get("/login/link/{$user->magic_link_token}")
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_a_token_cannot_be_reused(): void
    {
        $user = User::factory()->create();
        $token = $user->generateMagicToken();

        $this->get("/login/link/{$token}")->assertRedirect('/dashboard');
        Auth()->logout();

        $this->get("/login/link/{$token}")->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_an_invalid_token_redirects_to_login(): void
    {
        $this->get('/login/link/not-a-real-token')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_invite_acceptance_signs_the_user_in_automatically(): void
    {
        $user = User::factory()->invited()->create();

        $this->post("/invite/{$user->invite_token}", ['name' => $user->name]);

        $this->assertAuthenticatedAs($user);
    }

    // -------------------------------------------------------------------------
    // Mobile bridge — hands the token to the app via the gigwithme:// scheme.
    // -------------------------------------------------------------------------

    public function test_the_mobile_bridge_renders_the_deep_link(): void
    {
        $user = User::factory()->create();
        $token = $user->generateMagicToken();

        $this->get("/login/link/{$token}/app")
            ->assertOk()
            ->assertSee("gigwithme://exchange?token={$token}", false);
    }

    public function test_the_mobile_bridge_does_not_consume_the_token(): void
    {
        // The app's exchange endpoint consumes the token, not the bridge page,
        // so opening the bridge must leave the token usable.
        $user = User::factory()->create();
        $token = $user->generateMagicToken();

        $this->get("/login/link/{$token}/app")->assertOk();

        $this->assertTrue($user->fresh()->isValidMagicToken($token));
    }

    public function test_the_web_magic_link_email_targets_the_web_authenticate_route(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/login/link', ['email' => $user->email]);

        Notification::assertSentTo($user, MagicLinkNotification::class, function ($notification) use ($user) {
            $url = $notification->toMail($user)->actionUrl;

            return str_contains($url, '/login/link/') && ! str_ends_with($url, '/app');
        });
    }
}
