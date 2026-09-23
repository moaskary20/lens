<?php

namespace App\Filament\Vendor\Resources;

use App\Filament\Vendor\Concerns\OwnsVendorRecords;
use App\Filament\Vendor\Resources\MyMessageResource\Pages;
use App\Models\Conversation;
use App\Support\ChatThread;
use App\Support\Feature;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use UnitEnum;

class MyMessageResource extends Resource
{
    use OwnsVendorRecords;
    protected static ?string $model = Conversation::class;

    protected static ?string $slug = 'messages';

    protected static ?string $navigationLabel = 'Client chat';

    protected static ?string $modelLabel = 'conversation';

    protected static ?string $pluralModelLabel = 'conversations';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Bookings';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return Feature::enabled('chat')
            && (bool) auth()->user()?->isVendor()
            && auth()->user()?->vendor;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('vendor_id', auth()->user()?->vendor?->id ?: 0)
            ->with(['client', 'booking']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Placeholder::make('thread')
                ->hiddenLabel()
                ->content(fn (?Conversation $record): HtmlString => $record
                    ? ChatThread::html($record)
                    : new HtmlString('<p>No conversation selected.</p>'))
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client.name')->label('Client')->searchable(),
                TextColumn::make('booking.reference')->label('Session')->placeholder('—'),
                TextColumn::make('messages_count')->counts('messages')->label('Messages'),
                TextColumn::make('last_message_at')->label('Last message')->since(),
            ])
            ->recordActions([
                EditAction::make()->label('Open chat'),
            ])
            ->defaultSort('last_message_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyMessages::route('/'),
            'edit' => Pages\EditMyMessage::route('/{record}/edit'),
        ];
    }
}
