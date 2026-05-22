<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', static fn () => Inertia::render('Welcome'));

// activeBand / bands / stats / upcomingGigs are still mock props until the
// BandSessionService + gig stats land (docs/legacy-app-features.md §2). The
// authenticated user now comes from HandleInertiaRequests::share, so there's
// no auth override here — log in to see the real user in the layout.
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', static fn () => Inertia::render('Dashboard', [
        'activeBand' => ['id' => 1, 'name' => 'The Velvet Hours', 'genre' => 'Indie / Dream Pop', 'role' => 'owner'],
        'bands' => [
            ['id' => 1, 'name' => 'The Velvet Hours', 'genre' => 'Indie / Dream Pop', 'role' => 'owner'],
            ['id' => 2, 'name' => 'Neon Saturday', 'genre' => 'Synthwave', 'role' => 'admin'],
            ['id' => 3, 'name' => 'Open Mic Collective', 'genre' => 'Acoustic', 'role' => 'member'],
        ],
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
});

require __DIR__.'/auth.php';
