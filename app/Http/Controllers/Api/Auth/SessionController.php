<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends ApiController
{
    /**
     * The authenticated member's current identity (user + bands). The mobile
     * app calls this on launch to refresh its cached auth state, so changes
     * like being added to a band show up without signing out.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($this->identityPayload($request->user()));
    }
}
