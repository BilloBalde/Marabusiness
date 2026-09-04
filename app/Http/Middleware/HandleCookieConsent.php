<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\CookieConsentService;

class HandleCookieConsent
{
    protected CookieConsentService $cookieService;

    public function __construct(CookieConsentService $cookieService)
    {
        $this->cookieService = $cookieService;
    }

    public function handle(Request $request, Closure $next)
    {
        // Share cookie consent status with all views
        view()->share('showCookieBanner', $this->cookieService->shouldShowBanner());
        view()->share('cookieCategories', CookieConsentService::getCategories());

        // If consent exists, set up tracking based on preferences
        if ($this->cookieService->hasConsent()) {
            $settings = $this->cookieService->getConsentSettings();

            // Bind to container for use in other parts of the application
            app()->instance('cookie.consent', $settings);

            // You can set up your analytics/tracking here
            if ($settings['analytics'] ?? false) {
                // Enable Google Analytics, etc.
                view()->share('analyticsEnabled', true);
            }

            if ($settings['marketing'] ?? false) {
                // Enable marketing pixels
                view()->share('marketingEnabled', true);
            }
        }

        return $next($request);
    }
}