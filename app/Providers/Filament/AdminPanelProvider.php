<?php

namespace App\Providers\Filament;

use App\Filament\Auth\AdminLogin;
use App\Filament\Widgets\LatestBookings;
use App\Filament\Widgets\LensStatsOverview;
use App\Filament\Widgets\PendingVerifications;
use App\Filament\Widgets\QualityStatsOverview;
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

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(AdminLogin::class)
            ->brandName('Lens')
            ->favicon(asset('favicon.svg'))
            ->font('Inter')
            ->darkMode(true, true)
            ->colors([
                'primary' => Color::hex('#FF5A1F'),
                'danger' => Color::hex('#B73A0F'),
                'warning' => Color::hex('#F79646'),
                'success' => Color::hex('#3D8B5F'),
                'info' => Color::hex('#5C5F66'),
                'gray' => [
                    50 => '#F2EFE9',
                    100 => '#E4E0D8',
                    200 => '#C8C4BB',
                    300 => '#9A9892',
                    400 => '#7A7C82',
                    500 => '#5C5F66',
                    600 => '#3E4248',
                    700 => '#2A2D34',
                    800 => '#1A1B1F',
                    900 => '#121214',
                    950 => '#0D0D0F',
                ],
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                LensStatsOverview::class,
                LatestBookings::class,
                PendingVerifications::class,
                QualityStatsOverview::class,
            ])
            ->databaseNotifications()
            ->navigationGroups([
                'Users',
                'Marketplace',
                'Search Engine',
                'Bookings & Finance',
                'Quality & Support',
                'Content',
                'Settings',
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
