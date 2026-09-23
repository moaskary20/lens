<?php

namespace App\Filament\Resources\RecommendationRuleResource\Pages;

use App\Filament\Resources\RecommendationRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRecommendationRule extends EditRecord
{
    protected static string $resource = RecommendationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
