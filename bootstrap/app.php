<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        App\Providers\RateLimiterServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        // Render (and every PaaS like it) puts the app behind its own reverse
        // proxy — the app is never reached directly from the public internet.
        // Without this, Laravel reads $request->ip() as the proxy's own address
        // for every visitor (defeating the per-IP rate limiter, see
        // RateLimiterServiceProvider and LoginPage's own throttle — every guest
        // would share one bucket) and never sees the request as secure, so a
        // cookie can never be marked "secure" and $request->isSecure() is always
        // false. Trusting '*' is the standard, safe setting for this topology:
        // the platform's edge is the actual trust boundary, not this list.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'session.expiry' => \App\Http\Middleware\CheckSessionExpiry::class,
            'panel.timeout' => \App\Http\Middleware\MultiPanelSessionTimeout::class,
            'csrf.refresh' => \App\Http\Middleware\RefreshCsrfToken::class,
        ]);
        
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\CheckSessionExpiry::class,
            \App\Http\Middleware\MultiPanelSessionTimeout::class,
            \App\Http\Middleware\RefreshCsrfToken::class,
            \App\Http\Middleware\HandleCookieConsent::class,
        ]);
        
        $middleware->api(append: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\CheckSessionExpiry::class,
            \App\Http\Middleware\RefreshCsrfToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();