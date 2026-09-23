<?php

namespace App\Filament\Resources\DeliverableResource\Pages;

use App\Filament\Resources\DeliverableResource;
use App\Models\Booking;
use App\Services\DeliveryService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateDeliverable extends CreateRecord
{
    protected static string $resource = DeliverableResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $booking = Booking::query()->findOrFail($data['booking_id']);

        return app(DeliveryService::class)->upload(
            $booking,
            $data['path'],
            $data['original_name'] ?? null,
        );
    }
}
