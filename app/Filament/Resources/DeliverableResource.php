<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\DeliverableResource\Pages;
use App\Models\Deliverable;
use App\Support\DeliveryProtection;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class DeliverableResource extends Resource
{
    use HasOptionalFeature;

    protected static ?string $model = Deliverable::class;

    protected static ?string $featureKey = 'protected_delivery';

    protected static ?string $navigationLabel = 'Deliverables';

    protected static ?string $modelLabel = 'deliverable';

    protected static ?string $pluralModelLabel = 'deliverables';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Bookings & Finance';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        $settings = DeliveryProtection::settings();

        return $schema->components([
            Section::make('5.1 Protected upload')
                ->description('Files stay watermarked and download-locked until the client approves. Preview overlays are configured under Protected delivery.')
                ->schema([
                    Placeholder::make('protection')
                        ->label('Active preview rules')
                        ->content(implode(' · ', array_filter([
                            $settings['watermark_enabled'] ? 'Watermark: '.$settings['watermark_text'] : null,
                            $settings['anti_screenshot'] ? 'Anti-screenshot overlay' : null,
                            $settings['block_recording'] ? 'Screen recording blocked' : null,
                            $settings['block_download_until_approval'] ? 'Original download locked' : null,
                        ])))
                        ->columnSpanFull(),
                    Select::make('booking_id')->label('Booking')->relationship('booking', 'reference')->searchable()->preload()->required(),
                    FileUpload::make('path')->label('Retouched / edited file')->directory('deliverables')->required()
                        ->maxSize(fn (): int => \App\Support\StorageQuota::clientProjectMaxSizeKb())
                        ->helperText(fn (): string => 'Up to '.\App\Support\StorageQuota::clientProjectQuotaMb().' MB per client project. Auto-deleted '.\App\Support\StorageQuota::retentionDays().' days after the booking is closed.'),
                    TextInput::make('original_name')->label('File name'),
                    TextInput::make('version')->label('Version')->numeric()->helperText('Auto-incremented when uploaded through the delivery engine.'),
                    Toggle::make('is_watermarked')->label('Lens Protected watermark')->default(true),
                    Toggle::make('is_unlocked')->label('Unlocked for original download')
                        ->helperText('Forced off until Approve if download lock is enabled.')
                        ->disabled(fn (?Deliverable $record): bool => DeliveryProtection::downloadsLocked()
                            && ! in_array($record?->booking?->status, ['approved', 'completed'], true)),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking.reference')->label('Booking')->searchable(),
                TextColumn::make('original_name')->label('File'),
                TextColumn::make('version')->label('Version'),
                IconColumn::make('is_watermarked')->label('Watermarked')->boolean(),
                IconColumn::make('is_unlocked')->label('Unlocked')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeliverables::route('/'),
            'create' => Pages\CreateDeliverable::route('/create'),
            'edit' => Pages\EditDeliverable::route('/{record}/edit'),
        ];
    }
}
