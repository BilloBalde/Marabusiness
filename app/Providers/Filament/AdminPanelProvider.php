<?php

namespace App\Providers\Filament;

use App\Filament\Resources\OrderResource\Widgets\OrderStats;
use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\MenuItem;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Pages\Auth\EditProfile;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile(EditProfile::class)
            ->brandName('MARA BUSINESS')
            ->brandLogo(asset('assets/images/logo.png'))
            ->darkModeBrandLogo(asset('assets/images/logo.png'))
            ->favicon(asset('assets/images/favicon.ico'))
            ->brandLogoHeight('4rem')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            /* ->pages([
                Pages\Dashboard::class,
            ]) */
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            /* ->widgets([
                OrderStats::class,
            ]) */
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                \App\Http\Middleware\SetLocale::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            /* ->userMenuItems([
                 MenuItem::make()
                    ->label(__('Language'))
                    ->icon('heroicon-o-language')
                    ->url(fn() => route('filament.language.menu')),
            ]) */

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
            ]);
    }
}
