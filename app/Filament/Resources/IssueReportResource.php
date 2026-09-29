<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\IssueReportResource\Pages;
use App\Models\IssueReport;
use App\Support\LensNotifier;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class IssueReportResource extends Resource
{
    use HasOptionalFeature;

    protected static ?string $model = IssueReport::class;

    protected static ?string $featureKey = 'issue_reports';

    protected static ?string $staffCapability = 'triage_issues';

    protected static ?string $navigationLabel = 'App issues';

    protected static ?string $modelLabel = 'app issue';

    protected static ?string $pluralModelLabel = 'app issues';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|UnitEnum|null $navigationGroup = 'Quality & Support';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()->whereIn('status', ['open', 'reviewing'])->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Reporter')->schema([
                Placeholder::make('who')->hiddenLabel()->content(fn (?IssueReport $record): string => self::reporterLine($record))->columnSpanFull(),
                TextInput::make('name')->label('Name')->disabled()->dehydrated(false),
                TextInput::make('email')->label('Email')->disabled()->dehydrated(false),
                TextInput::make('phone')->label('Phone')->disabled()->dehydrated(false),
                TextInput::make('booking_reference')->label('Booking ref')->disabled()->dehydrated(false),
                TextInput::make('platform')->label('Device')->disabled()->dehydrated(false),
                TextInput::make('app_version')->label('App version')->disabled()->dehydrated(false),
            ])->columns(2),
            Section::make('Issue')->schema([
                TextInput::make('reference')->label('Ticket')->disabled()->dehydrated(false),
                Select::make('topic')->label('Topic')->options(IssueReport::TOPICS)->native(false)->disabled()->dehydrated(false),
                TextInput::make('subject')->label('Subject')->disabled()->dehydrated(false)->columnSpanFull(),
                Textarea::make('body')->label('Details')->rows(6)->disabled()->dehydrated(false)->columnSpanFull(),
            ])->columns(2),
            Section::make('Support')->schema([
                Select::make('status')
                    ->label('Status')
                    ->options(IssueReport::STATUSES)
                    ->native(false)
                    ->required(),
                Textarea::make('admin_notes')->label('Internal notes')->rows(4)->columnSpanFull(),
                Textarea::make('staff_reply')
                    ->label('Reply to reporter')
                    ->helperText('Saved replies are emailed / pushed to the app account when the reporter is signed in.')
                    ->rows(4)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->label('Ticket')->searchable()->sortable(),
                TextColumn::make('topic')->label('Topic')->badge()->formatStateUsing(fn (string $state): string => IssueReport::TOPICS[$state] ?? $state),
                TextColumn::make('subject')->label('Subject')->limit(42)->searchable(),
                TextColumn::make('name')->label('From')->searchable(),
                TextColumn::make('email')->label('Email')->toggleable(),
                TextColumn::make('status')->label('Status')->badge()->color(fn (string $state): string => match ($state) {
                    'resolved' => 'success',
                    'closed' => 'gray',
                    'reviewing' => 'info',
                    default => 'warning',
                }),
                TextColumn::make('created_at')->label('Received')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(IssueReport::STATUSES),
                SelectFilter::make('topic')->label('Topic')->options(IssueReport::TOPICS),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIssueReports::route('/'),
            'edit' => Pages\EditIssueReport::route('/{record}/edit'),
        ];
    }

    public static function notifyReporter(IssueReport $issue): void
    {
        $user = $issue->user;
        if (! $user) {
            $user = \App\Models\User::query()->where('email', $issue->email)->where('is_active', true)->first();
        }
        if (! $user) {
            return;
        }

        $body = filled($issue->staff_reply)
            ? $issue->staff_reply
            : 'Status is now '.$issue->status.'.';

        LensNotifier::toUser(
            $user,
            LensNotifier::ISSUE_UPDATED,
            $issue->reference.' update',
            $body,
        );
    }

    public static function reporterLine(?IssueReport $record): string
    {
        if (! $record) {
            return '—';
        }

        $who = $record->user?->name ?: $record->name;
        $role = $record->user?->role ?: 'guest';

        return $who.' · '.$record->email.' · '.$role;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference', 'subject', 'email', 'name'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        /** @var IssueReport $record */
        return ($record->reference ?: 'Issue').' · '.$record->subject;
    }
}
