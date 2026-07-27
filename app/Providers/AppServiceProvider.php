<?php

namespace App\Providers;

use App\Models\Person;
use App\Observers\PersonObserver;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\URL;
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
        Person::observe(PersonObserver::class);

        // The app is served behind a reverse proxy at a URL path prefix
        // (/familytree) that the backend request has no way to see on its
        // own, so URL generation must be pinned to the configured APP_URL
        // rather than derived from the request's Host header.
        if (config('app.url')) {
            URL::forceRootUrl(config('app.url'));
        }

        // Cloudflare and StackCDN sit in front of the live site, so without
        // this request()->ip() is the CDN's address rather than the visitor's
        // — and that is what login rate limiting counts against. Off unless
        // configured; see config/app.php for the reasoning.
        if ($proxies = config('app.trusted_proxies')) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }
    }
}
