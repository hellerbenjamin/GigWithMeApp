<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Password-less sign-in via a one-time email link. Primary path for band
 * members; available as a secondary option for admins alongside the password
 * form. Tokens expire in 15 minutes and are consumed on first use.
 */
class MagicLinkController extends Controller
{
    /**
     * Show the "email me a sign-in link" form.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/MagicLink');
    }

    /**
     * Send the magic link. Always shows success so we don't reveal whether
     * an email address is registered.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $token = $user->generateMagicToken();
            $user->notify(new MagicLinkNotification($token));
        }

        return back()->with('status', "We sent a sign-in link to {$request->email}. Check your inbox.");
    }

    /**
     * Authenticate via the one-time token from the email link.
     */
    public function authenticate(Request $request, string $token): RedirectResponse
    {
        $user = User::where('magic_link_token', $token)->first();

        if (! $user || ! $user->isValidMagicToken($token)) {
            return redirect()->route('login')->with(
                'status',
                'That sign-in link has expired or already been used. Request a new one.',
            );
        }

        $user->consumeMagicToken();

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
