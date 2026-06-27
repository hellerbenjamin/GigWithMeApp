<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends ApiController
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json(['data' => $this->userPayload($user)]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'timezone'],
        ]);

        $user->update($data);

        return response()->json(['data' => $this->userPayload($user->fresh())]);
    }

    public function avatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:5120'], // 5 MB
        ]);

        $user = $request->user();

        // Delete the old avatar if one exists.
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $request->file('avatar')->store('avatars', 'public');

        $user->update(['avatar_path' => $path]);

        return response()->json(['data' => $this->userPayload($user->fresh())]);
    }

    private function userPayload($user): array
    {
        return [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'timezone'    => $user->timezone,
            'avatar_url'  => $user->avatar_path
                ? Storage::disk('public')->url($user->avatar_path)
                : null,
        ];
    }
}
