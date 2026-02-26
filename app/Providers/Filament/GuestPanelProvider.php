<?php

namespace App\Providers\Filament;

use App\Auth\GuestUser;
use App\Filament\Widgets\RankingGeneralWidget;
use App\Http\Middleware\AssignGuestUser;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class GuestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('guest')
            ->path('invitado')
            ->brandName('Federacion Argentina de Billar')
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('3rem')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                RankingGeneralWidget::class,
            ])
            ->pages([
                Dashboard::class,
            ])
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn (): string => view('filament.dev-footer')->render(),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                SubstituteBindings::class,
                AssignGuestUser::class,
            ])
            ->authMiddleware([]) // ← IMPORTANTE: sin autenticación
            ->topNavigation();
    }

    public function afterBoot(): void
    {
        Filament::serving(function () {

            /** @var \Filament\Auth\AuthManager $auth */
            $auth = Filament::auth();

            // Usuario invitado para el panel público
            $auth->setUserResolver(fn () => new GuestUser);
        });
    }
}