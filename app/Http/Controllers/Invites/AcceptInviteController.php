<?php

namespace App\Http\Controllers\Invites;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The guest-facing invite acceptance: a newly added member taps the link in
 * their invitation email and confirms their name. Push notification setup
 * follows on a dedicated step. The unguessable invite_token in the URL is
 * the authorization.
 */
class AcceptInviteController extends Controller
{
    public function show(string $token): Response
    {
        $user = User::where('invite_token', $token)->firstOrFail();

        return Inertia::render('Invite/Accept', [
            'token' => $token,
            'memberName' => $user->name,
            'bandNames' => $user->bands()->orderBy('name')->pluck('name'),
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $user = User::where('invite_token', $token)->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user->name = $data['name'];
        $user->invite_token = null;
        $user->save();

        $pushToken = $user->ensurePushToken();

        return redirect()->route('invite.setup', $pushToken);
    }
}
