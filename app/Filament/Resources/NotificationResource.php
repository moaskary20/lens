<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\NotificationResource\Pages;
use App\Support\LensNotifier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use UnitEnum;

class NotificationResource extends Resource
{
    use HasOptionalFeature;

    protected static ?string $model = DatabaseNotification::class;

    protected static ?string $featureKey = 'notifications';

    protected static ?string $slug = 'notification-log';

    protected static ?string $navigationLabel = 'Notification log';

    protected static ?string $modelLabel = 'notification';

    protected static ?string $pluralModelLabel = 'notifications';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|UnitEnum|null $navigationGroup = 'Quality & Support';

    protected static ?int $navigationSort = 8;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Sent')->since()->sortable(),
                TextColumn::make('notifiable.email')->label('User')->searchable(),
                TextColumn::make('data.event')->label('Event')->badge()
                    ->formatStateUsing(fn (?string $state): string => LensNotifier::labels()[$state] ?? ($state ?: '—')),
                TextColumn::make('data.title')->label('Title')->wrap(),
                TextColumn::make('data.body')->label('Body')->limit(60)->wrap(),
                TextColumn::make('read_at')->label('Read')->since()->placeholder('Unread'),
            ])
            ->filters([
                SelectFilter::make('event')->label('Event')->options(LensNotifier::labels())
                    ->query(function ($query, array $data) {
                        if (! filled($data['value'] ?? null)) {
                            return $query;
                        }

                        return $query->where('data->event', $data['value']);
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifications::route('/'),
        ];
    }
}
