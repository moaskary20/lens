<?php

namespace App\Filament\Resources\VendorResource\Pages;

use App\Filament\Resources\VendorResource;
use App\Models\City;
use App\Models\Vendor;
use App\Models\VendorType;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Component;

class ListVendors extends ListRecords
{
    protected static string $resource = VendorResource::class;

    /**
     * @var array<string, mixed>
     */
    public array $vendorSearch = [];

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All vendors')
                ->badge(fn (): int => Vendor::query()->count()),
            'verified' => Tab::make('Vendor Verified')
                ->badge(fn (): int => Vendor::query()->where('verification_status', 'verified')->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('verification_status', 'verified')),
            'pending' => Tab::make('Vendor Pending')
                ->badge(fn (): int => Vendor::query()->where('verification_status', 'pending')->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('verification_status', 'pending')),
            'rejected' => Tab::make('Vendor Rejected')
                ->badge(fn (): int => Vendor::query()->where('verification_status', 'rejected')->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('verification_status', 'rejected')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('searchVendors')
                ->label('Search vendors')
                ->icon(Heroicon::OutlinedMagnifyingGlass)
                ->modalHeading('Search vendors')
                ->modalDescription('Combine any fields below to narrow the vendor list.')
                ->modalWidth('3xl')
                ->modalSubmitActionLabel('Search')
                ->fillForm(fn (Component $livewire): array => $livewire->vendorSearch)
                ->schema([
                    Section::make('Search criteria')
                        ->schema([
                            TextInput::make('name')->label('Name')->placeholder('Search vendor name')->maxLength(255),
                            Select::make('vendor_type_id')
                                ->label('Type')
                                ->options(fn (): array => VendorType::query()->orderBy('name_en')->pluck('name_en', 'id')->all())
                                ->searchable()
                                ->native(false),
                            Select::make('city_id')
                                ->label('Governorate')
                                ->options(fn (): array => City::query()->orderBy('name_en')->pluck('name_en', 'id')->all())
                                ->searchable()
                                ->native(false),
                            TextInput::make('rating_avg')->label('Rating')->numeric()->minValue(0)->maxValue(5),
                            TextInput::make('completed_sessions')->label('Completed')->numeric()->integer()->minValue(0),
                            TextInput::make('failed_sessions')->label('Failed')->numeric()->integer()->minValue(0),
                            DatePicker::make('date')->label('Date')->native(false),
                        ])
                        ->columns(2),
                ])
                ->action(function (array $data, Component $livewire): void {
                    if ($livewire instanceof self) {
                        $livewire->applyVendorSearch($data);
                    }
                }),
            Action::make('clearVendorSearch')
                ->label('Clear search')
                ->icon(Heroicon::OutlinedXMark)
                ->color('gray')
                ->visible(fn (Component $livewire): bool => $livewire instanceof self && $livewire->hasVendorSearch())
                ->action(function (Component $livewire): void {
                    if ($livewire instanceof self) {
                        $livewire->clearVendorSearch();
                    }
                }),
            CreateAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $search
     */
    public function applyVendorSearch(array $search): void
    {
        $this->vendorSearch = array_filter(
            $search,
            static fn (mixed $value): bool => filled($value),
        );
        $this->resetPage();
        $this->flushCachedTableRecords();
    }

    public function clearVendorSearch(): void
    {
        $this->vendorSearch = [];
        $this->resetPage();
        $this->flushCachedTableRecords();
    }

    public function hasVendorSearch(): bool
    {
        return $this->vendorSearch !== [];
    }

    protected function getTableQuery(): Builder|Relation|null
    {
        $query = parent::getTableQuery();

        if (! $query instanceof Builder) {
            return $query;
        }

        $search = $this->vendorSearch;

        if (filled($search['name'] ?? null)) {
            $query->where('display_name', 'like', '%'.$search['name'].'%');
        }

        if (filled($search['vendor_type_id'] ?? null)) {
            $query->where('vendor_type_id', $search['vendor_type_id']);
        }

        if (filled($search['city_id'] ?? null)) {
            $query->where('city_id', $search['city_id']);
        }

        foreach (['rating_avg', 'completed_sessions', 'failed_sessions'] as $column) {
            if (filled($search[$column] ?? null) && is_numeric($search[$column])) {
                $query->where($column, $search[$column]);
            }
        }

        if (filled($search['date'] ?? null)) {
            $query->whereDate('created_at', $search['date']);
        }

        return $query;
    }
}
