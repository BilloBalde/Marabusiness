<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SetLocale
{
    protected array $supportedLocales = ['en', 'fr', 'zh'];

    public function handle(Request $request, Closure $next)
    {
        $locale = $request->session()->get('locale', config('app.locale'));

        if (! in_array($locale, $this->supportedLocales, true)) {
            $locale = config('app.locale');
        }

        elseif (Auth::check() && Auth::user()->locale) {
            $locale = Auth::user()->locale;
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
