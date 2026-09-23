<?php

namespace App\Filament\Vendor\Concerns;

use App\Models\Dispute;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;

trait OwnsVendorRecords
{
    public static function canView(Model $record): bool
    {
        return static::ownsRecord($record);
    }

    public static function canEdit(Model $record): bool
    {
        return static::ownsRecord($record);
    }

    public static function canDelete(Model $record): bool
    {
        return static::ownsRecord($record);
    }

    public static function ownsRecord(Model $record): bool
    {
        $user = auth()->user();

        if (! $user?->isVendor()) {
            return false;
        }

        if ($record instanceof WalletTransaction) {
            $record->loadMissing('wallet');

            return (int) $record->wallet?->user_id === (int) $user->id;
        }

        if ($record instanceof Dispute) {
            $record->loadMissing('booking');

            return (int) $record->booking?->vendor_id === $user->vendorId();
        }

        if (! isset($record->vendor_id)) {
            return false;
        }

        return (int) $record->vendor_id === $user->vendorId();
    }
}
