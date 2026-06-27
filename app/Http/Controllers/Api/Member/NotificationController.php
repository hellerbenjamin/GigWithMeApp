<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Api\ApiController;
use App\Models\MobilePushToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationController extends ApiController
{
    // The timing options members can pick from — mirrors the web app.
    public const AVAILABLE_DAYS = [7, 3, 1, 0];

    /**
     * Register an Expo push token for the authenticated device.
     * Upserts by token so re-installs don't create duplicates.
     */
    public function registerToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token'       => ['required', 'string', 'starts_with:ExponentPushToken['],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        MobilePushToken::updateOrCreate(
            ['token' => $data['token']],
            ['user_id' => $request->user()->id, 'device_name' => $data['device_name'] ?? null],
        );

        return response()->json(['ok' => true]);
    }

    /**
     * Remove an Expo push token (called on logout from a specific device).
     */
    public function removeToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
        ]);

        MobilePushToken::where('user_id', $request->user()->id)
            ->where('token', $data['token'])
            ->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Return the member's current reminder preferences.
     */
    public function showPreferences(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'channels'      => $user->reminder_channels ?? ['email'],
                'days'          => $user->reminder_days ?? [7, 1],
                'available_days' => self::AVAILABLE_DAYS,
            ],
        ]);
    }

    /**
     * Save reminder preferences.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $data = $request->validate([
            'channels'   => ['present', 'array'],
            'channels.*' => [Rule::in(['email', 'push', 'mobile'])],
            'days'       => ['present', 'array'],
            'days.*'     => [Rule::in(self::AVAILABLE_DAYS)],
        ]);

        $request->user()->update([
            'reminder_channels' => array_values(array_unique($data['channels'])),
            'reminder_days'     => array_values(array_unique($data['days'])),
        ]);

        return $this->showPreferences($request);
    }
}
