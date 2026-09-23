<?php

namespace App\Filament\Vendor\Pages;

use App\Models\Vendor;
use App\Support\Finance;
use App\Support\VendorProfile;
use Filament\Forms\Components\Hidden;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use UnitEnum;

class MyPricing extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'My prices';

    protected static ?string $title = 'My prices';

    protected static string|UnitEnum|null $navigationGroup = 'Studio';

    protected static ?int $navigationSort = 1;

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isVendor()
            && auth()->user()?->roleCan('set_prices')
            && auth()->user()?->vendor;
    }

    public function mount(): void
    {
        $vendor = $this->vendor();
        $this->form->fill([
            'vendor_type_id' => $vendor->vendor_type_id,
            'half_day_price' => $vendor->half_day_price,
            'full_day_price' => $vendor->full_day_price,
            'hourly_price' => $vendor->hourly_price,
            'per_video_price' => $vendor->per_video_price,
            'extras' => $vendor->extras,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $vendor = $this->vendor();
        $model = $vendor?->resolvedPricingModel();

        return $schema
            ->components([
                Section::make($model?->name_en ?: 'Your rates')
                    ->description($model?->description ?: 'Set the amounts clients will see. The admin owns the pricing logic for your vendor type.')
                    ->schema([
                        Hidden::make('vendor_type_id'),
                        ...VendorProfile::pricingFields(),
                    ]),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Save my prices')->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $vendor = $this->vendor();
        $state = $this->form->getState();
        $extras = $vendor->extras ?? [];
        if (isset($state['extras']['prices']) && is_array($state['extras']['prices'])) {
            $extras['prices'] = $state['extras']['prices'];
        }

        $vendor->update([
            'half_day_price' => $state['half_day_price'] ?? $vendor->half_day_price,
            'full_day_price' => $state['full_day_price'] ?? $vendor->full_day_price,
            'hourly_price' => $state['hourly_price'] ?? $vendor->hourly_price,
            'per_video_price' => $state['per_video_price'] ?? $vendor->per_video_price,
            'extras' => $extras,
        ]);

        Notification::make()->title('Prices updated in '.Finance::currency())->success()->send();
    }

    protected function vendor(): Vendor
    {
        $vendor = auth()->user()?->vendor;

        abort_unless($vendor, 403);

        return $vendor;
    }
}
