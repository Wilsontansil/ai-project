<?php

namespace App\Providers;

use App\Support\MetricsCollector;
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
        // Force https so route() and url() always generate https:// URLs
        // whenever APP_URL itself is configured with https://.
        if (str_starts_with(config('app.url', ''), 'https://')) {
            URL::forceScheme('https');
        }

        // Flush buffered metrics at the end of every HTTP request.
        // For queue jobs, ProcessAiReply calls MetricsCollector::flush() explicitly.
        $this->app->terminating(fn () => MetricsCollector::flush());
    }
}
