<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The app sits behind an nginx reverse proxy that terminates SSL.
        // Force https so route() and url() always generate https:// URLs.
        if (config('app.env') !== 'local') {
            URL::forceScheme('https');
        }
    }
}
