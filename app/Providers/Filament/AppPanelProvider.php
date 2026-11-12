<?php

namespace App\Providers\Filament;

use App\Filament\App\Pages\Tenancy\RegisterBreeder;
use App\Http\Middleware\SetLocale;
use App\Models\Breeder;
use App\Support\LanguageMenu;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Panel for breeders: tenant-aware, every user sees only the data of the breeders they belong to.
 */
class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('app')
            ->path('app')
            ->login()
            ->tenant(Breeder::class, slugAttribute: 'slug')
            ->tenantRegistration(RegisterBreeder::class)
            ->userMenuItems(LanguageMenu::items())
            ->brandName('kith')
            ->brandLogo(asset('images/kith-logo-text.webp'))
            ->darkModeBrandLogo(asset('images/kith-logo-text-light.webp'))
            ->brandLogoHeight('1.75rem')
            ->favicon(asset('images/kith-logo.webp'))
            ->maxContentWidth('fi-width-8xl')
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn (): string => '<style>.fi-main.fi-width-8xl{max-width:88rem}</style>')
            ->colors([
                'primary' => Color::hex('#173b2b'),
            ])
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\Filament\App\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\Filament\App\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\Filament\App\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                SetLocale::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
