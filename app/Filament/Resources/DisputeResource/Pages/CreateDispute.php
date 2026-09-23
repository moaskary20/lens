<?php

namespace App\Filament\Resources\DisputeResource\Pages;

use App\Filament\Resources\DisputeResource;
use App\Services\DisputeService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use LogicException;

class CreateDispute extends CreateRecord
{
    protected static string $resource = DisputeResource::class;

    protected function afterCreate(): void
    {
        $service = app(DisputeService::class);
        $service->syncOpened($this->record);

        $record = $this->record->fresh();

        try {
            $message = DisputeResource::applyFormOutcome(
                $record,
                $record->status,
                $record->decision,
                $record->admin_notes,
            );

            if ($message) {
                Notification::make()->title($message)->success()->send();
            }
        } catch (LogicException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }
}
