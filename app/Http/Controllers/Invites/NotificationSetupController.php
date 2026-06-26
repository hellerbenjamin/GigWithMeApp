<?php

namespace App\Http\Controllers\Invites;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Post-acceptance push notification setup. Reached after accepting an invite;
 * the durable push_token in the URL is the authorization. The member sets up
 * (or skips) push here before continuing to sign in.
 */
class NotificationSetupController extends Controller
{
    public function show(string $pushToken): Response
    {
        User::where('push_token', $pushToken)->firstOrFail();

        return Inertia::render('Invite/Setup', [
            'pushToken' => $pushToken,
        ]);
    }
}
