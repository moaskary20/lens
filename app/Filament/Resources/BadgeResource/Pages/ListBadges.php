<?php

namespace App\Filament\Resources\BadgeResource\Pages;

use App\Filament\Resources\BadgeResource;
use App\Services\ReputationService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListBadges extends ListRecords
{
    protected static string $resource = BadgeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recalculate')
                ->label('Recalculate awards')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Re-reads ratings, completion rate, and failed sessions, then awards Top Rated / Popular and featured placement.')
                ->action(function (): void {
                    $count = app(ReputationService::class)->refreshAll();
                    Notification::make()->title("Updated badges for {$count} vendors")->success()->send();
                }),
            CreateAction::make(),
        ];
    }
}
