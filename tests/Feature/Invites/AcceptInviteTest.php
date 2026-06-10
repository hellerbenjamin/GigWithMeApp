<?php

namespace Tests\Feature\Invites;

use App\Models\Band;
use App\Models\Gig;
use App\Models\User;
use App\Notifications\GigConfirmed;
use App\Support\SmsConsent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The login-free invite acceptance: a member confirms their own number and opts
 * in to SMS, which is the only place an SMS consent record is created. See
 * docs/sms-consent-invite-flow.md.
 */
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
                ->where('optInText', SmsConsent::OPT_IN_TEXT)
                ->where('bandNames', ['The Night Owls'])
            );
    }

    public function test_an_unknown_token_404s(): void
    {
        $this->get('/invite/nope')->assertNotFound();
    }

    public function test_opting_in_records_consent_and_normalizes_the_number(): void
    {
        $user = $this->invitedMember();

        $this->post("/invite/{$user->invite_token}", [
            'name' => 'Jordan Reyes',
            'opt_in' => true,
            'phone_number' => '(555) 123-4567',
        ])->assertRedirect('/login')->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Jordan Reyes', $user->name);
        $this->assertSame('+15551234567', $user->phone_number);
        $this->assertNotNull($user->sms_consent_at);
        $this->assertSame(SmsConsent::OPT_IN_TEXT, $user->sms_consent_text);
        $this->assertNull($user->sms_opted_out_at);
        // One-time link is spent.
        $this->assertNull($user->invite_token);
        $this->assertTrue($user->hasSmsConsent());
    }

    public function test_accepting_without_opting_in_records_no_consent(): void
    {
        $user = $this->invitedMember();

        $this->post("/invite/{$user->invite_token}", [
            'name' => $user->name,
            'opt_in' => false,
        ])->assertRedirect('/login');

        $user->refresh();
        $this->assertNull($user->phone_number);
        $this->assertNull($user->sms_consent_at);
        $this->assertNull($user->invite_token);
        $this->assertFalse($user->hasSmsConsent());
    }

    public function test_opting_in_requires_a_phone_number(): void
    {
        $user = $this->invitedMember();

        $this->post("/invite/{$user->invite_token}", [
            'name' => $user->name,
            'opt_in' => true,
        ])->assertSessionHasErrors('phone_number');

        // Nothing recorded, token still live.
        $this->assertNull($user->fresh()->sms_consent_at);
        $this->assertNotNull($user->fresh()->invite_token);
    }

    public function test_sms_channel_is_gated_on_consent(): void
    {
        // via() doesn't touch the gig, so an unsaved one is enough.
        $notification = new GigConfirmed(new Gig);

        $consented = User::factory()->create(['phone_number' => '+15551112222']);
        $this->assertContains('vonage', $notification->via($consented));

        $noConsent = User::factory()->withoutSmsConsent()->create(['phone_number' => '+15551112222']);
        $this->assertNotContains('vonage', $notification->via($noConsent));

        $optedOut = User::factory()->smsOptedOut()->create(['phone_number' => '+15551112222']);
        $this->assertNotContains('vonage', $notification->via($optedOut));
    }
}
