<?php

namespace App\Filament\Pages\Quality;

use App\Support\Finance;
use App\Support\VendorMetrics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SessionMetricsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $metrics = VendorMetrics::platform();
        $currency = Finance::currency();

        return [
            Stat::make('Booked vs completed', $metrics['booked'].' / '.$metrics['completed'])
                ->description('Completion rate '.VendorMetrics::percent((float) $metrics['completion_rate']))
                ->color('primary'),
            Stat::make('Accepted vs rejected', $metrics['accepted'].' / '.$metrics['rejected'])
                ->description('Acceptance rate '.VendorMetrics::percent((float) $metrics['acceptance_rate']))
                ->color('success'),
            Stat::make('Failed sessions', (string) $metrics['failed'])
                ->description('Disputes and vendor no-shows')
                ->color('danger'),
            Stat::make('Applied penalties', number_format((float) $metrics['penalty_total'], 2).' '.$currency)
                ->description('Charged on failed / cancelled vendor sessions')
                ->color('warning'),
        ];
    }
}
