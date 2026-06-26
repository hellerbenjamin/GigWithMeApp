<?php

use App\Http\Controllers\BandMembers\BandMemberController;
use App\Http\Controllers\Bands\BandController;
use App\Http\Controllers\Bands\BandSettingsController;
use App\Http\Controllers\Bands\SetActiveBandController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Gigs\GigController;
use App\Http\Controllers\Invites\AcceptInviteController;
use App\Http\Controllers\Invites\NotificationSetupController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\Member\MemberHomeController;
use App\Http\Controllers\Push\PushSubscriptionController;
use App\Http\Controllers\Rsvp\RsvpController;
use App\Http\Controllers\VenueController;
use App\Http\Controllers\VenueImportController;
use App\Http\Middleware\HasActiveBand;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/login');

// Public legal pages — login-free and linked from the SMS opt-in screen; also
// the URLs we give our SMS provider (Vonage) for A2P registration.
Route::get('/privacy', static fn () => Inertia::render('Legal/Privacy'))->name('privacy');
Route::get('/terms', static fn () => Inertia::render('Legal/Terms'))->name('terms');

// Generic PWA manifest for non-personalized pages (login, marketing). Personal
// member pages link the token-bearing manifest above instead.
Route::get('/manifest.webmanifest', [ManifestController::class, 'generic'])->name('manifest');

// Magic-link member invite acceptance — public (no auth): the token in the URL
// is the authorization. After accepting, members are guided to set up push
// notifications before signing in. Throttled.
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/invite/{token}', [AcceptInviteController::class, 'show'])->name('invite.accept');
    Route::post('/invite/{token}', [AcceptInviteController::class, 'store'])->name('invite.submit');
    Route::get('/invite/setup/{pushToken}', [NotificationSetupController::class, 'show'])->name('invite.setup');
});

// Magic-link gig RSVP — deliberately public (no auth/active-band): the token in
// the URL is the authorization. Throttled since it's unauthenticated.
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/rsvp/{token}', [RsvpController::class, 'show'])->name('rsvp.show');
    Route::post('/rsvp/{token}', [RsvpController::class, 'update'])->name('rsvp.update');
    // Fired by the service worker from a push notification action (CSRF-exempt,
    // token-authorized — see bootstrap/app.php). Returns JSON, not a redirect.
    Route::post('/rsvp/{token}/reply', [RsvpController::class, 'reply'])->name('rsvp.reply');
});

// Login-free member surfaces, keyed by the durable per-member push_token: the
// personal home (also the installed PWA's start_url + manifest) and the push
// subscribe/unsubscribe endpoints. The token is the authorization; throttled
// since it's unauthenticated.
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/m/{pushToken}', [MemberHomeController::class, 'show'])->name('member.home');
    Route::get('/m/{pushToken}/manifest.webmanifest', [ManifestController::class, 'member'])
        ->name('member.manifest');
    Route::post('/push/subscribe/{pushToken}', [PushSubscriptionController::class, 'storeByToken'])
        ->name('push.subscribe.token');
    Route::delete('/push/subscribe/{pushToken}', [PushSubscriptionController::class, 'destroyByToken'])
        ->name('push.unsubscribe.token');
});

// Band creation sits outside the HasActiveBand group on purpose — a user with
// no bands must be able to reach it without being bounced back here. It's also
// where the switcher's "Create a new band" link lands.
Route::middleware('auth')->group(function () {
    Route::get('/bands/create', [BandController::class, 'create'])->name('bands.create');
    Route::post('/bands', [BandController::class, 'store'])->name('bands.store');
});

// activeBand and bands are shared globally by HandleInertiaRequests; the active
// band is resolved (and auto-selected) by HasActiveBand.
Route::middleware(['auth', HasActiveBand::class])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Push opt-in for logged-in owners/admins — keyed off the session user
    // rather than a token.
    Route::post('/push/subscribe', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::delete('/push/subscribe', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');

    Route::post('/bands/{band}/set-active', SetActiveBandController::class)
        ->name('bands.set-active');

    Route::get('/venues', [VenueController::class, 'index'])->name('venues.index');
    Route::get('/venues/create', [VenueController::class, 'create'])->name('venues.create');
    Route::post('/venues', [VenueController::class, 'store'])->name('venues.store');

    // Bulk CSV import — registered before the {venue} routes so the literal
    // "import" path can't be swallowed by model binding. The token names a
    // per-session temp upload; constrain it to a UUID so it can't reach
    // arbitrary paths.
    Route::get('/venues/import', [VenueImportController::class, 'create'])->name('venues.import.create');
    Route::post('/venues/import', [VenueImportController::class, 'parse'])->name('venues.import.parse');
    Route::get('/venues/import/{token}', [VenueImportController::class, 'map'])
        ->where('token', '[0-9a-fA-F-]{36}')->name('venues.import.map');
    Route::post('/venues/import/{token}', [VenueImportController::class, 'import'])
        ->where('token', '[0-9a-fA-F-]{36}')->name('venues.import.store');

    Route::get('/venues/{venue}/edit', [VenueController::class, 'edit'])->name('venues.edit');
    Route::put('/venues/{venue}', [VenueController::class, 'update'])->name('venues.update');
    Route::delete('/venues/{venue}', [VenueController::class, 'destroy'])->name('venues.destroy');

    Route::get('/gigs', [GigController::class, 'index'])->name('gigs.index');
    Route::get('/gigs/create', [GigController::class, 'create'])->name('gigs.create');
    Route::post('/gigs', [GigController::class, 'store'])->name('gigs.store');
    Route::get('/gigs/{gig}', [GigController::class, 'show'])->name('gigs.show');
    Route::get('/gigs/{gig}/edit', [GigController::class, 'edit'])->name('gigs.edit');
    Route::put('/gigs/{gig}', [GigController::class, 'update'])->name('gigs.update');
    Route::delete('/gigs/{gig}', [GigController::class, 'destroy'])->name('gigs.destroy');
    // Poll-mode booking actions, owner/admin-gated in the controller.
    Route::post('/gigs/{gig}/confirm', [GigController::class, 'confirm'])->name('gigs.confirm');
    Route::post('/gigs/{gig}/repoll', [GigController::class, 'rePoll'])->name('gigs.repoll');

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
