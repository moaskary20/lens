<?php

namespace App\Filament\Resources\CouponResource\Pages;

use App\Filament\Resources\CouponResource;
use App\Support\LensNotifier;
use Filament\Resources\Pages\CreateRecord;

class CreateCoupon extends CreateRecord
{
    protected static string $resource = CouponResource::class;

    protected function afterCreate(): void
    {
        LensNotifier::announceOffer($this->record->fresh(['assignedUsers', 'user', 'vendor.user']));
    }
}
