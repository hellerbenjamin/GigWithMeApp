<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\MobilePushToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    /**
     * Revoke the Sanctum token used for this request.
     */
    public function destroy(Request $request): JsonResponse
    {
        // If the client passes its Expo push token, remove it so the device
        // stops receiving notifications after sign-out.
        if ($pushToken = $request->input('push_token')) {
            MobilePushToken::where('user_id', $request->user()->id)
                ->where('token', $pushToken)
                ->delete();
        }

        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Signed out.']);
    }
}
