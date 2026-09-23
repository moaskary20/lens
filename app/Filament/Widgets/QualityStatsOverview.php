<?php

namespace App\Filament\Widgets;

use App\Support\Feature;
use App\Support\VendorMetrics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QualityStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 4;

    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isStaff()
            && (Feature::enabled('reviews') || Feature::enabled('badges') || Feature::enabled('bookings'));
    }

    protected function getStats(): array
    {
        $metrics = VendorMetrics::platform();

        return [
            Stat::make('Marketplace rating', ($metrics['rating_avg'] ?: '—').' ★')
                ->description($metrics['review_count'].' visible client reviews')
                ->color('warning'),
            Stat::make('Session completion', VendorMetrics::percent((float) $metrics['completion_rate']))
                ->description($metrics['completed'].' completed of '.$metrics['booked'].' booked')
                ->color('success'),
            Stat::make('Failed + penalties', (string) $metrics['failed'])
                ->description(number_format((float) $metrics['penalty_total'], 2).' EGP applied')
                ->color('danger'),
            Stat::make('Incentive badges', $metrics['top_rated'].' Top Rated / '.$metrics['popular'].' Popular')
                ->description($metrics['featured'].' featured placements')
                ->color('primary'),
        ];
    }
}
