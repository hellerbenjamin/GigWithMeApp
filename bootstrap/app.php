<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Support\Cloudflare;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/mobile.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Real visitor IPs (and https) from behind Cloudflare; see App\Support\Cloudflare.
        $middleware->trustProxies(
            at: Cloudflare::PROXY_RANGES,
            headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO,
        );

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
