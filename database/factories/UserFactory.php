<?php

namespace Database\Factories;

use App\Models\User;
use App\Support\SmsConsent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            // Seeded members are treated as fully onboarded: they've accepted and
            // opted in to SMS. Use invited()/withoutSmsConsent() to test the gate.
            'sms_consent_at' => now(),
            'sms_consent_text' => SmsConsent::OPT_IN_TEXT,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * A member who hasn't opted in to SMS (no consent on record). They should
     * never be routed an SMS even with a phone number on file.
     */
    public function withoutSmsConsent(): static
    {
        return $this->state(fn (array $attributes) => [
            'sms_consent_at' => null,
            'sms_consent_text' => null,
        ]);
    }

    /**
     * A pending invite: a brand-new account that hasn't accepted yet — no SMS
     * consent, carrying an invite_token the acceptance link uses.
     */
    public function invited(): static
    {
        return $this->state(fn (array $attributes) => [
            'sms_consent_at' => null,
            'sms_consent_text' => null,
            'invite_token' => Str::random(64),
        ]);
    }

    /**
     * A member who opted in, then replied STOP — consent on record but opted out,
     * so SMS must not send.
     */
    public function smsOptedOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'sms_consent_at' => now()->subDay(),
            'sms_consent_text' => SmsConsent::OPT_IN_TEXT,
            'sms_opted_out_at' => now(),
        ]);
    }
}
