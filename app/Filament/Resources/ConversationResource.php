<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\ConversationResource\Pages;
use App\Models\Conversation;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ConversationResource extends Resource
{
    use HasOptionalFeature;

    protected static ?string $model = Conversation::class;

    protected static ?string $featureKey = 'chat';

    protected static ?string $navigationLabel = 'Conversations';

    protected static ?string $modelLabel = 'conversation';

    protected static ?string $pluralModelLabel = 'conversations';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Quality & Support';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('client_id')->label('Client')->relationship('client', 'name')->searchable()->preload()->required(),
            Select::make('vendor_id')->label('Vendor')->relationship('vendor', 'display_name')->searchable()->preload()->required(),
            Select::make('booking_id')->label('Booking')->relationship('booking', 'reference')->searchable()->preload(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client.name')->label('Client')->searchable(),
                TextColumn::make('vendor.display_name')->label('Vendor')->searchable(),
                TextColumn::make('booking.reference')->label('Booking'),
                TextColumn::make('messages_count')->counts('messages')->label('Messages'),
                TextColumn::make('last_message_at')->label('Last message')->since(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConversations::route('/'),
            'create' => Pages\CreateConversation::route('/create'),
            'edit' => Pages\EditConversation::route('/{record}/edit'),
        ];
    }
}
