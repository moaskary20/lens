<?php

namespace App\Filament\Resources\DisputeResource\Pages;

use App\Filament\Resources\DisputeResource;
use App\Models\Dispute;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use LogicException;

class EditDispute extends EditRecord
{
    protected static string $resource = DisputeResource::class;

    protected ?string $intendedStatus = null;

    protected ?string $intendedDecision = null;

    protected function getHeaderActions(): array
    {
        /** @var Dispute $record */
        $record = $this->getRecord();

        $actions = DisputeResource::decisionActions();
        foreach ($actions as $action) {
            $action->record($record);
        }

        $actions[] = DeleteAction::make()->visible(fn (): bool => $record->isOpen());

        return $actions;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->intendedStatus = isset($data['status']) ? (string) $data['status'] : null;
        $this->intendedDecision = isset($data['decision']) && $data['decision'] !== '' ? (string) $data['decision'] : null;

        unset($data['status'], $data['decision']);

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var Dispute $record */
        $record = $this->getRecord()->fresh();

        try {
            $message = DisputeResource::applyFormOutcome(
                $record,
                $this->intendedStatus,
                $this->intendedDecision,
                $record->admin_notes,
            );

            if ($message) {
                Notification::make()->title($message)->success()->send();
            }
        } catch (LogicException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }

        $this->record = $this->getRecord()->fresh();
        $this->refreshFormData(['status', 'decision']);
    }
}
