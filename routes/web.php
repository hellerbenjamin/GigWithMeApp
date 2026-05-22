<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Demo data for the app-layout build. This all moves to real controllers +
// the BandSessionService once auth and the active-band backend are ported
// (see docs/legacy-app-features.md). Until then the route hands the Dashboard
// page mock props so the layout can be seen running.
$dashboard = static fn () => Inertia::render('Dashboard', [
    // Overrides the null shared auth.user from HandleInertiaRequests for the
    // demo — remove once real session auth exists.
    'auth' => ['user' => ['id' => 1, 'name' => 'Casey Rivera', 'email' => 'casey@roadie.test']],
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
]);

Route::get('/', $dashboard);
Route::get('/dashboard', $dashboard)->name('dashboard');
