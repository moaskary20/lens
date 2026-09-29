<?php

namespace App\Filament\Resources\IssueReportResource\Pages;

use App\Filament\Resources\IssueReportResource;
use App\Models\IssueReport;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIssueReport extends EditRecord
{
    protected static string $resource = IssueReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (in_array($data['status'] ?? null, ['resolved', 'closed'], true) && blank($this->record->resolved_at)) {
            $data['resolved_at'] = now();
        }

        if (in_array($data['status'] ?? null, ['open', 'reviewing'], true)) {
            $data['resolved_at'] = null;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var IssueReport $issue */
        $issue = $this->record;
        if (filled($issue->staff_reply) || in_array($issue->status, ['resolved', 'closed'], true)) {
            IssueReportResource::notifyReporter($issue);
        }
    }
}
