<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/mobile.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        // The push quick-reply is fired by the service worker from a notification
        // action — there's no page, so no CSRF token. The unguessable per-gig
        // token in the URL is the authorization, exactly as for the magic link.
        $middleware->validateCsrfTokens(except: [
            'rsvp/*/reply',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
