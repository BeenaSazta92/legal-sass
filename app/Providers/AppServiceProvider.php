<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

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
        RateLimiter::for('api', function (Request $request) {
            if ($request->user()) {
                // Authenticated user: higher limit
                return Limit::perMinute(100)->by($request->user()->id);
            } else {
                // Guest: lower limit
                return Limit::perMinute(10)->by($request->ip());
            }
        });
    }
}
