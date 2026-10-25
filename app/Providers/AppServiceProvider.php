<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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
        // ngrok terminates HTTPS at its edge and forwards the request to Laravel
        // over HTTP. TrustProxies makes the request scheme available, while this
        // fallback also protects URL generation in queued / console contexts.
        if (config('app.force_https') || request()->header('x-forwarded-proto') === 'https') {
            URL::forceHttps();
        }

        if ($origin = config('app.public_origin')) {
            URL::useOrigin(rtrim($origin, '/'));
        }

        Vite::prefetch(concurrency: 3);
    }
}
