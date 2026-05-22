<?php

namespace App\Providers;

use App\Services\BandSessionService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Scoped so the service's resolved-band cache lives for one request and
        // the ActiveBand facade resolves this same instance.
        $this->app->scoped(BandSessionService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
