<?php

namespace App\Providers\Filament;

use App\Filament\Vendor\Pages\MyPricing;
use App\Filament\Vendor\Pages\MyProfile;
use App\Filament\Vendor\Widgets\VendorRequestStats;
use App\Support\LensBrand;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class VendorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->id('vendor')
            ->path('vendor')
            ->login()
            ->brandName('Lens Vendor')
            ->favicon(asset('favicon.svg'))
            ->font('Inter')
            ->darkMode(true, true)
            ->colors([
                'primary' => Color::hex('#FF5A1F'),
                'danger' => Color::hex('#B73A0F'),
                'warning' => Color::hex('#F79646'),
                'success' => Color::hex('#3D8B5F'),
                'info' => Color::hex('#5C5F66'),
            ])
            ->discoverResources(in: app_path('Filament/Vendor/Resources'), for: 'App\\Filament\\Vendor\\Resources')
            ->pages([
                Dashboard::class,
                MyProfile::class,
                MyPricing::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Vendor/Widgets'), for: 'App\\Filament\\Vendor\\Widgets')
            ->widgets([
                VendorRequestStats::class,
            ])
            ->databaseNotifications()
            ->navigationGroups([
                'Account',
                'Studio',
                'Bookings',
                'Finance',
            ])
            ->sidebarCollapsibleOnDesktop();

        return LensBrand::apply($panel)
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
