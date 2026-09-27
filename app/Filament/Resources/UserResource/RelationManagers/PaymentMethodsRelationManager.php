<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\User;
use App\Support\ClientPayment;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PaymentMethodsRelationManager extends RelationManager
{
    protected static string $relationship = 'paymentMethods';

    protected static ?string $title = 'Payment methods';

    protected static bool $isLazy = false;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof User && $ownerRecord->isClient();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')->label('Type')->options([
                'card' => 'Bank card',
                'wallet' => 'Mobile wallet',
                'paypal' => 'PayPal',
            ])->required()->native(false),
            TextInput::make('label')->label('Label'),
            Toggle::make('is_default')->label('Default'),
            KeyValue::make('details')
                ->label('Sanitized details')
                ->keyLabel('Field')
                ->valueLabel('Value')
                ->helperText('Never store the full card number or CVV. Last 4 digits only.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')->label('Type')->badge(),
                TextColumn::make('label')->label('Label'),
                TextColumn::make('details.card_last4')->label('Last 4')->placeholder('—'),
                IconColumn::make('is_default')->label('Default')->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add method')
                    ->mutateFormDataUsing(function (array $data): array {
                        $type = $data['type'] ?? 'card';
                        $details = ClientPayment::sanitize($type, $data['details'] ?? []);
                        $data['details'] = $details;
                        $data['label'] = $data['label'] ?: ClientPayment::label($type, $details);

                        return $data;
                    }),
            ])
            ->recordActions([
                DeleteAction::make(),
            ]);
    }
}
