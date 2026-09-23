<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use AlizHarb\ModularLuncher\Filament\Plugins\ModularLuncherPlugin;   
use AlizHarb\ThemerLuncher\Filament\Plugins\ThemerLuncherPlugin;     
//use App\Filament\Central\Pages\Dashboard;

class CentralPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('central')
			->domain(env('CENTRAL_DOMAIN', 'nxtsaasnet.test'))
            ->path('admin')
            ->login()
            ->authGuard('central')          // custom guard — see 6.5
            ->colors([
                'primary' => Color::Indigo,
            ])
			->plugin(ModularLuncherPlugin::make())
			->plugin(ThemerLuncherPlugin::make())
            ->discoverResources(
                in: app_path('Filament/Central/Resources'),
                for: 'App\\Filament\\Central\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Central/Pages'),
                for: 'App\\Filament\\Central\\Pages'
            )
            ->pages([
                //Pages\Dashboard::class,
				//Dashboard::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/Central/Widgets'),
                for: 'App\\Filament\\Central\\Widgets'
            )
            ->widgets([
                Widgets\AccountWidget::class,
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
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}