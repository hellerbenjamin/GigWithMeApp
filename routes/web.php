<?php

use App\Http\Controllers\BandMembers\BandMemberController;
use App\Http\Controllers\Bands\BandController;
use App\Http\Controllers\Bands\BandSettingsController;
use App\Http\Controllers\Bands\SetActiveBandController;
use App\Http\Controllers\Gigs\GigController;
use App\Http\Controllers\VenueController;
use App\Http\Middleware\HasActiveBand;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', static fn () => Inertia::render('Welcome'));

// Band creation sits outside the HasActiveBand group on purpose — a user with
// no bands must be able to reach it without being bounced back here. It's also
// where the switcher's "Create a new band" link lands.
Route::middleware('auth')->group(function () {
    Route::get('/bands/create', [BandController::class, 'create'])->name('bands.create');
    Route::post('/bands', [BandController::class, 'store'])->name('bands.store');
});

// activeBand and bands are shared globally by HandleInertiaRequests; the active
// band is resolved (and auto-selected) by HasActiveBand. stats / upcomingGigs
// stay mock until gig stats land (docs/legacy-app-features.md §2).
Route::middleware(['auth', HasActiveBand::class])->group(function () {
    Route::get('/dashboard', static fn () => Inertia::render('Dashboard', [
        'stats' => [
            'upcomingGigs' => 4,
            'bookedThisMonth' => '$3,200',
            'venues' => 7,
            'members' => 5,
        ],
        'upcomingGigs' => [
            ['id' => 1, 'name' => 'Friday Night Headline', 'venue' => 'The Echo Lounge', 'date' => '2026-05-29', 'status' => 'confirmed', 'fee' => '$1,200'],
            ['id' => 2, 'name' => null, 'venue' => 'Riverside Amphitheater', 'date' => '2026-06-06', 'status' => 'pending', 'fee' => '$800'],
            ['id' => 3, 'name' => 'Summer Kickoff', 'venue' => 'The Basement', 'date' => '2026-06-14', 'status' => 'confirmed', 'fee' => '$650'],
            ['id' => 4, 'name' => 'Acoustic Sunday', 'venue' => 'Corner Cafe', 'date' => '2026-06-21', 'status' => 'pending', 'fee' => null],
        ],
    ]))->name('dashboard');

    Route::post('/bands/{band}/set-active', SetActiveBandController::class)
        ->name('bands.set-active');

    Route::get('/venues', [VenueController::class, 'index'])->name('venues.index');
    Route::get('/venues/create', [VenueController::class, 'create'])->name('venues.create');
    Route::post('/venues', [VenueController::class, 'store'])->name('venues.store');
    Route::get('/venues/{venue}/edit', [VenueController::class, 'edit'])->name('venues.edit');
    Route::put('/venues/{venue}', [VenueController::class, 'update'])->name('venues.update');
    Route::delete('/venues/{venue}', [VenueController::class, 'destroy'])->name('venues.destroy');

    Route::get('/gigs', [GigController::class, 'index'])->name('gigs.index');
    Route::get('/gigs/create', [GigController::class, 'create'])->name('gigs.create');
    Route::post('/gigs', [GigController::class, 'store'])->name('gigs.store');
    Route::get('/gigs/{gig}/edit', [GigController::class, 'edit'])->name('gigs.edit');
    Route::put('/gigs/{gig}', [GigController::class, 'update'])->name('gigs.update');
    Route::delete('/gigs/{gig}', [GigController::class, 'destroy'])->name('gigs.destroy');

    Route::get('/band-members', [BandMemberController::class, 'index'])->name('band-members.index');
    Route::get('/band-members/create', [BandMemberController::class, 'create'])->name('band-members.create');
    Route::post('/band-members', [BandMemberController::class, 'store'])->name('band-members.store');
    Route::get('/band-members/{user}/edit', [BandMemberController::class, 'edit'])->name('band-members.edit');
    Route::put('/band-members/{user}', [BandMemberController::class, 'update'])->name('band-members.update');
    Route::delete('/band-members/{user}', [BandMemberController::class, 'destroy'])->name('band-members.destroy');

    // Band settings live with the active band, so they sit behind HasActiveBand.
    Route::get('/settings', [BandSettingsController::class, 'edit'])->name('settings.index');
    Route::put('/settings', [BandSettingsController::class, 'update'])->name('settings.update');

    // Band-scoped feature areas — placeholders until their controllers exist.
    Route::get('/music', static fn () => Inertia::render('Music/Index'))->name('music.index');
});

require __DIR__.'/auth.php';
