<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\ApiController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends ApiController
{
    /**
     * Authenticate with email and password, returning a Sanctum bearer token.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'       => ['required', 'email'],
            'password'    => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        return $this->tokenResponse($user, $data['device_name']);
    }
}
