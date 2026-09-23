<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\ConversationResource\Pages;
use App\Models\Conversation;
use App\Support\ChatThread;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
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
            Section::make('Participants')
                ->schema([
                    Select::make('client_id')->label('Client')->relationship('client', 'name')->searchable()->preload()->required(),
                    Select::make('vendor_id')->label('Vendor')->relationship('vendor', 'display_name')->searchable()->preload()->required(),
                    Select::make('booking_id')->label('Booking')->relationship('booking', 'reference')->searchable()->preload(),
                ])
                ->columns(3),
            Section::make('Live chat')
                ->description('The same thread the client opens in Chat in App. Staff replies appear on the vendor conversation.')
                ->schema([
                    Placeholder::make('thread')
                        ->hiddenLabel()
                        ->content(fn (?Conversation $record) => $record
                            ? ChatThread::html($record)
                            : new HtmlString('<div class="lens-live-chat"><div class="lens-live-chat__empty"><p>Save the conversation to see messages.</p></div></div>'))
                        ->columnSpanFull(),
                ]),
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
