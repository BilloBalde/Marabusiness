<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Resources\ProductResource;
use Filament\Panel;
use Filament\Pages\Auth\EditProfile;
use Filament\Navigation\MenuItem;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class VendorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('vendor')
            ->path('vendor')
            ->login()
            ->profile(EditProfile::class)
            ->brandName('MARA BUSINESS')
            ->brandLogo(asset('assets/images/logo.png'))
            ->darkModeBrandLogo(asset('assets/images/logo.png'))
            ->favicon(asset('assets/images/favicon.ico'))
            ->brandLogoHeight('4rem')
            ->discoverResources(in: app_path('Filament/Vendor/Resources'), for: 'App\\Filament\\Vendor\\Resources')
            ->discoverPages(in: app_path('Filament/Vendor/Pages'), for: 'App\\Filament\\Vendor\\Pages')
            ->discoverWidgets(in: app_path('Filament/Vendor/Widgets'), for: 'App\\Filament\\Vendor\\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                \App\Http\Middleware\SetLocale::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DispatchServingFilamentEvent::class,
            ])
            ->userMenuItems([
               \Filament\Navigation\MenuItem::make('fr')
                    ->label('🇫🇷 Français')
                    ->url(fn () => route('lang.switch', 'fr'))
                    ->visible(true),

                \Filament\Navigation\MenuItem::make('en')
                    ->label('🇬🇧 English')
                    ->url(fn () => route('lang.switch', 'en'))
                    ->visible(true),

                \Filament\Navigation\MenuItem::make('zh')
                    ->label('🇨🇳 中文')
                    ->url(fn () => route('lang.switch', 'zh'))
                    ->visible(true),
            ])
            /* ->topbarActions([
                LocaleSwitcher::make()
                    ->locales([
                        'en' => 'English',
                        'fr' => 'Français',
                        'zh' => '中文',
                    ]),
            ]) */

            ->authMiddleware([
                Authenticate::class,
            ])
            ->resources([
                \App\Filament\Vendor\Resources\BulkRfqResource::class,
                ProductResource::class,
            ]);
    }
}
