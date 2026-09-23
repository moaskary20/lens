<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class FavoritesRelationManager extends RelationManager
{
    protected static string $relationship = 'favorites';

    protected static ?string $title = 'Saved creators';

    protected static bool $isLazy = false;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof User && $ownerRecord->isClient();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('vendor_id')->label('Vendor')->relationship('vendor', 'display_name')->searchable()->preload()->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('vendor.display_name')->label('Vendor'),
                TextColumn::make('vendor.vendorType.name_en')->label('Type'),
                TextColumn::make('created_at')->label('Saved')->since(),
            ])
            ->headerActions([
                CreateAction::make()->label('Save creator'),
            ])
            ->recordActions([
                DeleteAction::make()->label('Remove'),
            ]);
    }
}
