<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Quality\SessionMetricsOverview;
use App\Filament\Pages\Quality\VendorMetricsTable;
use App\Support\Feature;
use App\Support\Roles;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SessionMetrics extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static ?string $navigationLabel = 'Session metrics';

    protected static ?string $title = 'Sessions booked, completed, accepted, rejected, failed';

    protected static string|UnitEnum|null $navigationGroup = 'Quality & Support';

    protected static ?int $navigationSort = 1;

    public function getSubheading(): ?string
    {
        return 'Booked vs completed, accepted vs rejected, and failed sessions with applied penalties.';
    }

    public static function canAccess(): bool
    {
        return Roles::staffCan(auth()->user(), 'view_operations')
            && Feature::enabled('bookings');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * @return array<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            SessionMetricsOverview::class,
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getFooterWidgets(): array
    {
        return [
            VendorMetricsTable::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }
}
