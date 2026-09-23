<?php

namespace App\Filament\Resources\FilterGroupResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'options';

    protected static ?string $title = 'Filter options';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name_en')->label('Label')->required(),
            TextInput::make('name_ar')->label('Arabic label'),
            TextInput::make('slug')->label('Slug')->required(),
            TextInput::make('helper_text')->label('Helper text')
                ->helperText('Example: 50mm / 85mm f/1.2 – f/1.4'),
            TagsInput::make('synonyms')->label('AI synonyms')
                ->helperText('Everyday words the assistant can map to this option.'),
            TextInput::make('unit')->label('Unit')->placeholder('cm, years, EU'),
            TextInput::make('min_value')->label('Min value')->numeric(),
            TextInput::make('max_value')->label('Max value')->numeric(),
            Toggle::make('is_active')->label('Enabled')->default(true),
            Toggle::make('show_in_quick_filters')->label('Show in quick filters'),
            TextInput::make('sort_order')->label('Sort')->numeric()->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name_en')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name_en')->label('Option'),
                TextColumn::make('slug')->label('Slug'),
                IconColumn::make('is_active')->label('On')->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['group_key'] = $this->getOwnerRecord()->slug;
                        $data['vendor_type_slugs'] = $this->getOwnerRecord()->vendor_type_slugs;

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
