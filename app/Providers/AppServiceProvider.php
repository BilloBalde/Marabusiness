<?php

namespace App\Providers;

use App\Http\Livewire\CustomerSidebar as LivewireCustomerSidebar;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Laravel\Socialite\Facades\Socialite;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Laravel 11 no longer auto-wires app/Exceptions/Handler.php the way
        // Laravel 10 did — bootstrap/app.php's withExceptions() binds the
        // framework's own base Handler class to the ExceptionHandler contract,
        // and App\Exceptions\Handler (which extends that same base class, with
        // its own register()/render() overrides for 419/403/500/503 pages and
        // CSRF/403/500 logging) was never actually resolved by anything. Every
        // page it was meant to render, and everything it was meant to log, was
        // running the framework's plain default instead. Rebinding here — a
        // provider's register() runs after withExceptions()'s own binding — is
        // what makes the container hand out our subclass instead.
        $this->app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \App\Exceptions\Handler::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Livewire::component('customer-sidebar', LivewireCustomerSidebar::class);

        $this->routeSocialiteThroughProxy();
        $this->logQueriesWhenAsked();
    }

    /**
     * Send Google and Facebook sign-in through an HTTP proxy — but only when one is
     * actually configured. This used to run unconditionally: with no PROXY_HOST set it
     * built the address "http://:" and handed it to Guzzle, so social sign-in failed on
     * every install that has no proxy. TLS verification is no longer disabled either;
     * turning it off silences certificate errors instead of fixing them.
     */
    private function routeSocialiteThroughProxy(): void
    {
        $host = config('services.proxy.host');
        $port = config('services.proxy.port');

        if (blank($host) || blank($port)) {
            return;
        }

        $proxy = "http://{$host}:{$port}";

        $providers = [
            'google'   => \Laravel\Socialite\Two\GoogleProvider::class,
            'facebook' => \Laravel\Socialite\Two\FacebookProvider::class,
        ];

        foreach ($providers as $name => $provider) {
            Socialite::extend($name, function () use ($name, $provider, $proxy) {
                return Socialite::buildProvider($provider, config("services.{$name}"))
                    ->setHttpClient(new Client([
                        'proxy'   => $proxy,
                        'timeout' => 30,
                    ]));
            });
        }
    }

    /**
     * Query logging is opt-in through LOG_QUERIES, not tied to APP_DEBUG. Bound to debug
     * it wrote one log line per query — dozens per page load, bindings included, which
     * means customer ids and e-mail addresses in a file that never rotates.
     */
    private function logQueriesWhenAsked(): void
    {
        if (! config('app.log_queries')) {
            return;
        }

        DB::listen(function ($query) {
            Log::info('Query: ' . $query->sql, $query->bindings);
        });
    }
}
