<?php

namespace App\Filament\Resources\ReviewResource\Pages;

use App\Filament\Resources\ReviewResource;
use App\Models\Booking;
use App\Services\ReviewService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateReview extends CreateRecord
{
    protected static string $resource = ReviewResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $booking = Booking::query()->findOrFail($data['booking_id']);

        return app(ReviewService::class)->submit(
            $booking,
            (int) $data['rating'],
            $data['comment'] ?? null,
            (bool) ($data['is_visible'] ?? true),
        );
    }
}
