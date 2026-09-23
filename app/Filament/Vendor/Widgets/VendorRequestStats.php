<?php

namespace App\Filament\Vendor\Widgets;

use App\Models\Booking;
use App\Services\WalletService;
use App\Support\Finance;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VendorRequestStats extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $vendor = auth()->user()?->vendor;
        $query = Booking::query()->where('vendor_id', $vendor?->id ?: 0);
        $wallet = auth()->user() ? app(WalletService::class)->ensure(auth()->user()) : null;

        $verification = match ($vendor?->verification_status) {
            'verified' => 'Verified',
            'rejected' => 'Rejected',
            default => 'Pending',
        };

        return [
            Stat::make('Verification', $verification)
                ->description($vendor?->vendorType?->name_en ?: 'Vendor type')
                ->color(match ($vendor?->verification_status) {
                    'verified' => 'success',
                    'rejected' => 'danger',
                    default => 'warning',
                }),
            Stat::make('Pending requests', (clone $query)->where('status', 'pending')->count())
                ->description('Accept or reject from Incoming requests')
                ->color('warning'),
            Stat::make('Accepted', (clone $query)->where('status', 'accepted')->count())
                ->color('success'),
            Stat::make('Pending earnings', number_format((float) ($wallet?->pending ?? 0), 0).' '.Finance::currency())
                ->description('In escrow — not yet available')
                ->color('info'),
            Stat::make('Available / withdrawn', number_format((float) ($wallet?->available ?? 0), 0).' / '.number_format((float) ($wallet?->lifetime_withdrawn ?? 0), 0).' '.Finance::currency())
                ->description('Wallet balance and paid-out total')
                ->color('success'),
            Stat::make('Your listed rates', collect([
                $vendor?->half_day_price,
                $vendor?->full_day_price,
                $vendor?->hourly_price,
                $vendor?->per_video_price,
            ])->filter()->map(fn ($amount) => number_format((float) $amount, 0).' '.Finance::currency())->implode(' · ') ?: 'Set prices')
                ->description($vendor?->vendorType?->pricingModel?->name_en ?: 'Pricing model')
                ->color('primary'),
        ];
    }
}
