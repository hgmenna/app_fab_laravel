<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Widgets\PlayersByClubChart;
use App\Filament\Widgets\RankingGeneralWidget;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('Federacion Argentina de Billar')
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('3rem')
            ->favicon(asset('images/logo.png'))
            ->globalSearch(false)
            ->login(Login::class)
            ->passwordReset()
            ->profile()
            ->emailChangeVerification()
            ->emailVerification()
            ->registration()
            ->theme('app')
            ->topNavigation()
            ->globalSearch(false)
            
            ->colors([
                'primary' => Color::Amber,
            ])

            ->viteTheme('resources/css/filament/admin/theme.css')

            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn (): string => view('filament.dev-footer')->render(),
            )

            ->breadcrumbs(false)

            ->databaseNotifications()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])

            ->widgets([
                RankingGeneralWidget::class,
                PlayersByClubChart::class,
            ])
            
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->scopeToTenant(false), 
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
