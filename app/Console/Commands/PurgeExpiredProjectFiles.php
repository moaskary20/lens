<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Support\StorageQuota;
use Illuminate\Console\Command;

class PurgeExpiredProjectFiles extends Command
{
    protected $signature = 'lens:purge-expired-project-files';

    protected $description = 'Delete client project and delivered files after the configured retention period.';

    public function handle(): int
    {
        $days = StorageQuota::retentionDays();
        $cutoff = now()->subDays($days);
        $removed = 0;

        Booking::query()
            ->with('deliverables')
            ->whereIn('status', ['completed', 'approved', 'cancelled', 'rejected', 'failed', 'refunded'])
            ->where(function ($query) use ($cutoff): void {
                $query->where('approved_at', '<=', $cutoff)
                    ->orWhere('cancelled_at', '<=', $cutoff)
                    ->orWhere(function ($inner) use ($cutoff): void {
                        $inner->whereNull('approved_at')
                            ->whereNull('cancelled_at')
                            ->where('updated_at', '<=', $cutoff);
                    });
            })
            ->each(function (Booking $booking) use (&$removed): void {
                $removed += StorageQuota::purgeBookingFiles($booking);
            });

        $this->info("Removed {$removed} expired client project file(s) after {$days} days.");

        return self::SUCCESS;
    }
}
