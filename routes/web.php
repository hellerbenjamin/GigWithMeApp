<?php

use App\Http\Controllers\Bands\SetActiveBandController;
use App\Http\Middleware\HasActiveBand;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', static fn () => Inertia::render('Welcome'));

// Band creation is deferred; this is a placeholder so the switcher's "Create a
// new band" link and the no-bands redirect have somewhere to land. It sits
// outside the HasActiveBand group on purpose — a user with no bands must be
// able to reach it without being bounced back here.
Route::middleware('auth')->group(function () {
    Route::get('/bands/create', static fn () => Inertia::render('Bands/Create'))
        ->name('bands.create');
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
});

require __DIR__.'/auth.php';
