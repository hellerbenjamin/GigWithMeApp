<?php

namespace App\Http\Controllers\Push;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Stores and removes a browser's web-push subscription. Two ways in, same body:
 *
 * - by durable push_token — login-free, for ordinary members opting in from the
 *   RSVP page or their installed PWA (the token is the authorization);
 * - by session user — for logged-in owners/admins toggling push in settings.
 *
 * Each member can have several subscriptions (one per browser/device); they're
 * keyed by endpoint, so re-subscribing the same browser is idempotent.
 */
class PushSubscriptionController extends Controller
{
    public function storeByToken(Request $request, string $pushToken): Response
    {
        $member = User::where('push_token', $pushToken)->firstOrFail();

        return $this->store($request, $member);
    }

    public function destroyByToken(Request $request, string $pushToken): Response
    {
        $member = User::where('push_token', $pushToken)->firstOrFail();

        return $this->destroy($request, $member);
    }

    public function store(Request $request, ?User $member = null): Response
    {
        $member ??= $request->user();

        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
            'contentEncoding' => ['nullable', 'string'],
        ]);

        $member->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth'],
            $data['contentEncoding'] ?? null,
        );

        return response()->noContent();
    }

    public function destroy(Request $request, ?User $member = null): Response
    {
        $member ??= $request->user();

        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
        ]);

        $member->deletePushSubscription($data['endpoint']);

        return response()->noContent();
    }
}
