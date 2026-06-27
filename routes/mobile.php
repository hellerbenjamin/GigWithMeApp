<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\MagicLinkController;
use App\Http\Controllers\Api\Auth\LogoutController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        // Request a magic sign-in link by email.
        Route::post('magic-link', [MagicLinkController::class, 'store'])
            ->middleware('throttle:6,1');

        // Exchange an invite token or magic-link token for a Sanctum bearer token.
        Route::post('magic-link/exchange', [MagicLinkController::class, 'exchange'])
            ->middleware('throttle:10,1');

        // Password fallback.
        Route::post('login', [LoginController::class, 'store'])
            ->middleware('throttle:5,1');

        // Revoke the current device token.
        Route::post('logout', [LogoutController::class, 'destroy'])
            ->middleware('auth:sanctum');
    });
});
