<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_register_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_new_users_can_register_and_are_logged_in(): void
    {
        $response = $this->post('/register', [
            'name' => 'Casey Rivera',
            'email' => 'casey@roadie.test',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', ['email' => 'casey@roadie.test']);
    }

    public function test_users_can_authenticate_with_valid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');
    }

    public function test_users_cannot_authenticate_with_an_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_users_can_log_out(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_guests_are_redirected_from_the_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
