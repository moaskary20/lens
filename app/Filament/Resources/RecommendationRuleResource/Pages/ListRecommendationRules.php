<?php

namespace App\Filament\Resources\RecommendationRuleResource\Pages;

use App\Filament\Resources\RecommendationRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRecommendationRules extends ListRecords
{
    protected static string $resource = RecommendationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
