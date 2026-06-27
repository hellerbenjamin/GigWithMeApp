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

        $bands = $user->bands()
            ->orderBy('name')
            ->get(['bands.id', 'bands.name', 'bands.slug'])
            ->map(fn ($band) => [
                'id'   => $band->id,
                'name' => $band->name,
                'slug' => $band->slug,
                'role' => $band->pivot->role,
            ]);

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
            'bands'      => $bands,
        ]);
    }
}
