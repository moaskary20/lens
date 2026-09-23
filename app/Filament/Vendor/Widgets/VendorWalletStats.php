<?php

namespace App\Filament\Vendor\Widgets;

use App\Services\WalletService;
use App\Support\Finance;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VendorWalletStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $user = auth()->user();
        $wallet = $user ? app(WalletService::class)->ensure($user) : null;
        $currency = Finance::currency();

        return [
            Stat::make('Total earnings', number_format((float) ($wallet?->lifetime_earned ?? 0), 0).' '.$currency)
                ->description('Released to your wallet after commission')
                ->color('primary'),
            Stat::make('Available', number_format((float) ($wallet?->available ?? 0), 0).' '.$currency)
                ->description('Ready to withdraw')
                ->color('success'),
            Stat::make('Pending', number_format((float) ($wallet?->pending ?? 0), 0).' '.$currency)
                ->description('Held in escrow until approval')
                ->color('warning'),
            Stat::make('Withdrawn', number_format((float) ($wallet?->lifetime_withdrawn ?? 0), 0).' '.$currency)
                ->description('Paid out to your bank / InstaPay')
                ->color('info'),
            Stat::make('Commissions', number_format((float) ($wallet?->lifetime_commission ?? 0), 0).' '.$currency)
                ->description('Platform share deducted from sessions')
                ->color('danger'),
        ];
    }
}
