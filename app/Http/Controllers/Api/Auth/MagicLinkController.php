<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MagicLinkController extends Controller
{
    /**
     * Send a magic sign-in link to the given email address.
     *
     * Always returns 200 so valid addresses are not revealed.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $token = $user->generateMagicToken();
            $user->notify(new MagicLinkNotification($token));
        }

        return response()->json([
            'message' => 'If that email is registered, a sign-in link is on its way.',
        ]);
    }

    /**
     * Exchange a one-time token for a Sanctum bearer token.
     *
     * Accepts either an invite_token (new member accepting their invitation)
     * or a magic_link_token (returning member signing in). For invite tokens,
     * an optional name field lets the member confirm/update their display name
     * in the same step.
     */
    public function exchange(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token'       => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
            'name'        => ['sometimes', 'string', 'max:255'],
        ]);

        // Invite token — new member accepting their invitation.
        $user = User::where('invite_token', $data['token'])->first();
        if ($user) {
            if (isset($data['name'])) {
                $user->name = $data['name'];
            }
            $user->invite_token = null;
            $user->save();

            return $this->tokenResponse($user, $data['device_name']);
        }

        // Magic link token — returning member signing in.
        $user = User::where('magic_link_token', $data['token'])->first();
        if ($user && $user->isValidMagicToken($data['token'])) {
            $user->consumeMagicToken();

            return $this->tokenResponse($user, $data['device_name']);
        }

        return response()->json([
            'message' => 'This sign-in link has expired or already been used.',
        ], 401);
    }

    private function tokenResponse(User $user, string $deviceName): JsonResponse
    {
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'token'      => $token,
            'token_type' => 'Bearer',
            'user'       => [
                'id'           => $user->id,
                'name'         => $user->name,
                'email'        => $user->email,
                'phone_number' => $user->phone_number,
                'avatar_path'  => $user->avatar_path,
                'timezone'     => $user->timezone,
            ],
        ]);
    }
}
