<?php

namespace App\Providers;

use App\Http\Livewire\CustomerSidebar as LivewireCustomerSidebar;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Laravel\Socialite\Facades\Socialite;
use GuzzleHttp\Client;

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
        Livewire::component('customer-sidebar', LivewireCustomerSidebar::class);
        $proxy = 'http://' . env('PROXY_HOST') . ':' . env('PROXY_PORT');

        // Inject proxy into Google
        Socialite::extend('google', function () use ($proxy) {
            $config = config('services.google');
            return Socialite::buildProvider(\Laravel\Socialite\Two\GoogleProvider::class, $config)
                ->setHttpClient(new Client([
                    'proxy'  => $proxy,
                    'verify' => false,
                    'timeout' => 30,
                ]));
        });

        // Inject proxy into Facebook
        Socialite::extend('facebook', function () use ($proxy) {
            $config = config('services.facebook');
            return Socialite::buildProvider(\Laravel\Socialite\Two\FacebookProvider::class, $config)
                ->setHttpClient(new Client([
                    'proxy'  => $proxy,
                    'verify' => false,
                    'timeout' => 30,
                ]));
        });
        if (config('app.debug')) {
            \DB::listen(function ($query) {
                \Log::info('Query: ' . $query->sql, $query->bindings);
            });
        }
    }
}
