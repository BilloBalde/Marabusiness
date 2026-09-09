<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RateLimiterServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Deliberately not attached to any route. It is IP-only, and both real
        // login endpoints (the customer Livewire form and the mobile API) now
        // throttle by email+IP instead — see LoginPage::save() and
        // AuthController::login(). Attaching this on top would reintroduce the
        // exact problem an email-aware key exists to avoid: several genuine
        // customers behind one shared IP would trip a single IP-wide cap on each
        // other's unrelated login attempts. Left registered in case a separate,
        // more generous, purely anti-flood IP cap is ever wanted later.
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}