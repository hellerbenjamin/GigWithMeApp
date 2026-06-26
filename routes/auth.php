<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('login/link', [MagicLinkController::class, 'create'])->name('login.magic');
    Route::post('login/link', [MagicLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('login.magic.send');
});

// Authenticate via token — accessible even when logged in (direct link from email).
Route::get('login/link/{token}', [MagicLinkController::class, 'authenticate'])
    ->middleware('throttle:10,1')
    ->name('login.magic.authenticate');

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
