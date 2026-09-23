<?php

namespace App\Filament\Resources\VendorResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PortfoliosRelationManager extends RelationManager
{
    protected static string $relationship = 'portfolios';

    protected static ?string $title = 'Previous projects';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return match ($ownerRecord->vendorType?->slug) {
            'videographer' => 'Video reel highlights',
            'ugc' => 'Short-form UGC samples',
            'reels' => 'Reel & social samples',
            'studio' => 'Studio gallery',
            'food_stylist' => 'Food styling portfolio',
            default => 'Previous projects',
        };
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')->label('Item type')->options([
                'image' => 'Photo',
                'video' => 'Video',
                'link' => 'Social / external link',
            ])->default('image')->live()->required(),
            TextInput::make('title')->label('Project title')->required(),
            TextInput::make('completed_on')->label('Completed on')->placeholder('2025 or Jun 2025'),
            FileUpload::make('path')->label('Photo / video file')
                ->directory('portfolios')
                ->visible(fn (Get $get): bool => in_array($get('type'), ['image', 'video'], true))
                ->maxSize(fn (): int => \App\Support\StorageQuota::portfolioMaxSizeKb($this->getOwnerRecord()->vendorType?->slug))
                ->helperText(fn (): string => 'This vendor category may store up to '.\App\Support\StorageQuota::portfolioQuotaMb($this->getOwnerRecord()->vendorType?->slug).' MB of portfolio files.'),
            TextInput::make('external_url')->label('Sample URL')
                ->url()
                ->visible(fn (Get $get): bool => in_array($get('type'), ['link', 'video'], true)),
            Textarea::make('description')->label('What was delivered')->rows(3)->columnSpanFull(),
            Toggle::make('is_featured')->label('Feature this project'),
            TextInput::make('sort_order')->label('Sort order')->numeric()->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')->label('Project')->searchable(),
                TextColumn::make('type')->label('Type')->badge(),
                TextColumn::make('completed_on')->label('Completed'),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['path'] = filled($data['path'] ?? null) ? $data['path'] : '';

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['path'] = filled($data['path'] ?? null) ? $data['path'] : '';

                        return $data;
                    }),
                DeleteAction::make(),
            ]);
    }
}
