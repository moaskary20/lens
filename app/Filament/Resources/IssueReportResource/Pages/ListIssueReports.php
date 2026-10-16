<?php

namespace App\Filament\Resources\IssueReportResource\Pages;

use App\Filament\Resources\IssueReportResource;
use App\Models\IssueReport;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListIssueReports extends ListRecords
{
    protected static string $resource = IssueReportResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('All issues')
                ->badge(fn (): int => IssueReport::query()->count()),
        ];

        foreach (IssueReport::STATUSES as $status => $label) {
            $tabs[$status] = Tab::make($label)
                ->badge(fn (): int => IssueReport::query()->where('status', $status)->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', $status));
        }

        return $tabs;
    }
}
