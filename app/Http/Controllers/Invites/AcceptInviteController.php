<?php

namespace App\Http\Controllers\Invites;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\SmsConsent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The guest-facing invite acceptance: a newly added member taps the link in
 * their invitation email and, login-free, confirms their own mobile number and
 * opts in to gig texts. The unguessable invite_token in the URL is the
 * authorization. This is the only place an SMS consent record is created, which
 * is what keeps adding a member from ever starting texts on its own. See
 * docs/sms-consent-invite-flow.md.
 */
class AcceptInviteController extends Controller
{
    /**
     * Show the acceptance page for an invite token.
     */
    public function show(string $token): Response
    {
        $user = User::where('invite_token', $token)->firstOrFail();

        return Inertia::render('Invite/Accept', [
            'token' => $token,
            'memberName' => $user->name,
            'bandNames' => $user->bands()->orderBy('name')->pluck('name'),
            'phoneNumber' => $user->phone_number,
            // The exact opt-in wording, shown next to the checkbox and stored
            // verbatim with the consent record. Single source of truth.
            'optInText' => SmsConsent::OPT_IN_TEXT,
        ]);
    }

    /**
     * Record the member's acceptance: their confirmed name, and — if they ticked
     * the opt-in box — their own number plus the SMS consent record. Declining
     * the box still accepts the invite; they just stay email/push only. The
     * one-time token is cleared either way.
     */
    public function store(Request $request, string $token): RedirectResponse
    {
        $user = User::where('invite_token', $token)->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'opt_in' => ['boolean'],
            // The number is only needed (and only required) when opting in to SMS.
            'phone_number' => [
                Rule::requiredIf(fn (): bool => (bool) $request->boolean('opt_in')),
                'nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-().]{7,}$/',
            ],
        ], [
            'phone_number.required' => 'Add the mobile number you want texts at, or untick the box.',
            'phone_number.regex' => 'That doesn\'t look like a phone number.',
        ]);

        $optedIn = (bool) ($data['opt_in'] ?? false);

        $user->name = $data['name'];

        if ($optedIn) {
            $user->phone_number = $this->normalizePhone($data['phone_number']);
            $user->sms_consent_at = now();
            $user->sms_consent_text = SmsConsent::OPT_IN_TEXT;
            $user->sms_opted_out_at = null;
        }

        // One-time link: clear the token so it can't be replayed to alter consent.
        $user->invite_token = null;
        $user->save();

        $message = $optedIn
            ? "You're all set — we'll text you about your band's gigs."
            : "You're all set. We'll reach you by email; you can turn on texts later.";

        return to_route('login')->with('success', $message);
    }

    /**
     * Best-effort E.164 normalization for US numbers (the app's A2P market):
     * keep digits, default the +1 country code for 10-digit input. A number that
     * already carries a country code is preserved.
     */
    private function normalizePhone(string $input): string
    {
        if (Str::startsWith(trim($input), '+')) {
            return '+'.preg_replace('/\D/', '', $input);
        }

        $digits = preg_replace('/\D/', '', $input);

        if (strlen($digits) === 10) {
            return '+1'.$digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            return '+'.$digits;
        }

        return '+'.$digits;
    }
}
