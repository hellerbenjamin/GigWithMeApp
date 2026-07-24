<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

abstract class ApiController extends Controller
{
    protected function tokenResponse(User $user, string $deviceName): JsonResponse
    {
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'token'      => $token,
            'token_type' => 'Bearer',
            ...$this->identityPayload($user),
        ]);
    }

    /**
     * The current user and their bands — the shape the mobile app caches as its
     * auth state. Shared by the token response (sign-in) and the /auth/me
     * refresh so a member's band list stays current after they're added to or
     * removed from a band.
     *
     * @return array{user: array<string, mixed>, bands: \Illuminate\Support\Collection}
     */
    protected function identityPayload(User $user): array
    {
        $bands = $user->bands()
            ->orderBy('name')
            ->get(['bands.id', 'bands.name', 'bands.slug'])
            ->map(fn ($band) => [
                'id'   => $band->id,
                'name' => $band->name,
                'slug' => $band->slug,
                'role' => $band->pivot->role,
            ]);

        return [
            'user'  => [
                'id'           => $user->id,
                'name'         => $user->name,
                'email'        => $user->email,
                'phone_number' => $user->phone_number,
                'avatar_path'  => $user->avatar_path,
                'timezone'     => $user->timezone,
            ],
            'bands' => $bands,
        ];
    }
}
