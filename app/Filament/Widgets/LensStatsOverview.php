<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Dispute;
use App\Models\User;
use App\Models\Vendor;
use App\Support\Finance;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LensStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isStaff();
    }

    protected function getStats(): array
    {
        $held = Booking::query()->where('escrow_status', 'held')->sum('total_paid');
        $revenue = Booking::query()->whereIn('status', ['approved', 'completed'])->sum('vendor_commission');

        return [
            Stat::make('Clients', User::query()->where('role', 'client')->count())
                ->description('Client accounts')
                ->icon(Heroicon::OutlinedUsers)
                ->chart($this->weekSeries(User::class, fn ($query) => $query->where('role', 'client')))
                ->color('gray'),
            Stat::make('Vendors', Vendor::query()->count())
                ->description('Pending verification: '.Vendor::query()->where('verification_status', 'pending')->count())
                ->icon(Heroicon::OutlinedCamera)
                ->chart($this->weekSeries(Vendor::class))
                ->color('primary'),
            Stat::make('Bookings', Booking::query()->count())
                ->description('Open disputes: '.Dispute::query()->where('status', 'open')->count())
                ->icon(Heroicon::OutlinedCalendarDays)
                ->chart($this->weekSeries(Booking::class))
                ->color('warning'),
            Stat::make('Commission revenue', number_format((float) $revenue, 2).' '.Finance::currency())
                ->description('Held in escrow: '.number_format((float) $held, 2).' '.Finance::currency())
                ->icon(Heroicon::OutlinedBanknotes)
                ->chart($this->weekSeries(Booking::class))
                ->color('success'),
        ];
    }

    /**
     * @param  class-string  $model
     * @param  callable(\Illuminate\Database\Eloquent\Builder): \Illuminate\Database\Eloquent\Builder|null  $scope
     * @return list<float>
     */
    protected function weekSeries(string $model, ?callable $scope = null): array
    {
        return collect(range(6, 0))->map(function (int $days) use ($model, $scope): float {
            $query = $model::query()->whereDate('created_at', now()->subDays($days));
            if ($scope) {
                $query = $scope($query);
            }

            return (float) $query->count();
        })->all();
    }
}
