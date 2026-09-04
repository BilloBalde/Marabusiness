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