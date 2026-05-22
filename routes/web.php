<?php

use App\Http\Controllers\Bands\BandController;
use App\Http\Controllers\Bands\SetActiveBandController;
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

    // User-scoped (not tied to the active band) — placeholder for now.
    Route::get('/settings', static fn () => Inertia::render('Settings/Index'))
        ->name('settings.index');
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

    // Band-scoped feature areas — placeholders until their controllers exist.
    Route::get('/gigs', static fn () => Inertia::render('Gigs/Index'))->name('gigs.index');
    Route::get('/band-members', static fn () => Inertia::render('BandMembers/Index'))->name('band-members.index');
    Route::get('/music', static fn () => Inertia::render('Music/Index'))->name('music.index');
});

require __DIR__.'/auth.php';
