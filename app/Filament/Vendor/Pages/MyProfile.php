<?php

namespace App\Filament\Vendor\Pages;

use App\Models\Category;
use App\Models\FilterTag;
use App\Models\Vendor;
use App\Support\Feature;
use App\Support\VendorProfile;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class MyProfile extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $navigationLabel = 'My profile';

    protected static ?string $title = 'My profile';

    protected static string|UnitEnum|null $navigationGroup = 'Account';

    protected static ?int $navigationSort = 1;

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isVendor() && auth()->user()?->vendor;
    }

    public function mount(): void
    {
        $vendor = $this->vendor()->loadMissing(['vendorType', 'categories', 'filterTags', 'badges']);

        $this->form->fill([
            'vendor_type_id' => $vendor->vendor_type_id,
            'display_name' => $vendor->display_name,
            'cover_image' => $vendor->cover_image,
            'profile_photo' => $vendor->profile_photo,
            'national_id_image' => $vendor->national_id_image,
            'date_of_birth' => $vendor->date_of_birth,
            'profession' => $vendor->profession,
            'bio' => $vendor->bio,
            'contact_phone' => $vendor->contact_phone ?: $vendor->user?->phone,
            'contact_email' => $vendor->contact_email ?: $vendor->user?->email,
            'whatsapp' => $vendor->whatsapp,
            'instagram' => $vendor->instagram,
            'city_id' => $vendor->city_id,
            'accepts_out_of_governorate' => $vendor->accepts_out_of_governorate,
            'default_travel_fee' => $vendor->default_travel_fee,
            'category_ids' => $vendor->categories()->pluck('categories.id')->all(),
            'filter_tag_ids' => $vendor->filterTags()->pluck('filter_tags.id')->all(),
            'specialties' => $vendor->specialties,
            'delivery_formats' => $vendor->delivery_formats,
            'turnaround_hours' => $vendor->turnaround_hours,
            'extras' => $vendor->extras,
            'bank_name' => $vendor->bank_name,
            'bank_account_holder' => $vendor->bank_account_holder,
            'bank_account_number' => $vendor->bank_account_number,
            'bank_iban' => $vendor->bank_iban,
            'instapay' => $vendor->instapay,
            'transfer_notes' => $vendor->transfer_notes,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $vendor = $this->vendor()->loadMissing(['vendorType', 'badges']);

        return $schema
            ->components([
                Hidden::make('vendor_type_id'),
                Section::make('Personal details')
                    ->description('Identity, national ID, age, profession, and photo used for verification.')
                    ->schema([
                        Placeholder::make('vendor_type_label')
                            ->label('Vendor type')
                            ->content($vendor->vendorType?->name_en ?: 'Assigned by Lens admin'),
                        TextInput::make('display_name')->label('Display name')->required(),
                        FileUpload::make('cover_image')->label('Cover image')->image()->directory('vendors/covers')->columnSpanFull(),
                        ...VendorProfile::personalFields(),
                    ])->columns(2),
                Section::make('Verification')
                    ->description('Staff review your national ID and profile. You cannot change this status yourself.')
                    ->visible(fn (): bool => Feature::enabled('verification'))
                    ->schema([
                        Placeholder::make('verification_status_label')
                            ->label('Status')
                            ->content(match ($vendor->verification_status) {
                                'verified' => 'Verified — you can receive bookings.',
                                'rejected' => 'Rejected'.($vendor->verification_notes ? ': '.$vendor->verification_notes : '.'),
                                default => 'Pending review by Lens staff.',
                            }),
                        Placeholder::make('badges_view')
                            ->label('Incentive badges')
                            ->content($vendor->badges->pluck('name_en')->filter()->implode(', ') ?: 'No badges yet')
                            ->visible(fn (): bool => Feature::enabled('badges')),
                    ])->columns(2),
                Section::make('Contact')
                    ->schema(VendorProfile::contactFields())
                    ->columns(2),
                Section::make('Work areas')
                    ->description('Home governorate and whether you travel for sessions.')
                    ->schema(VendorProfile::workAreaFields())
                    ->columns(2),
                Section::make('Services')
                    ->schema([
                        Select::make('category_ids')
                            ->label('Services you offer')
                            ->multiple()
                            ->options(fn (): array => Category::query()->where('is_active', true)->orderBy('sort_order')->pluck('name_en', 'id')->all())
                            ->searchable()
                            ->preload(),
                        Select::make('specialties')
                            ->label('Specialties')
                            ->multiple()
                            ->options(VendorProfile::specialtyOptions())
                            ->visible(fn (): bool => ! in_array($vendor->vendorType?->slug, ['photographer'], true)),
                        Select::make('filter_tag_ids')
                            ->label('Filter tags')
                            ->helperText('Equipment, rooms, and experience tags clients use to find you.')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(function () use ($vendor): array {
                                $slug = $vendor->vendorType?->slug;
                                $query = FilterTag::query()->where('is_active', true)->orderBy('sort_order');

                                if ($slug) {
                                    $query->where(function ($inner) use ($slug) {
                                        $inner->whereNull('vendor_type_slugs')
                                            ->orWhereJsonLength('vendor_type_slugs', 0)
                                            ->orWhereJsonContains('vendor_type_slugs', $slug);
                                    });
                                }

                                return $query->pluck('name_en', 'id')->all();
                            })
                            ->visible(fn (): bool => Feature::enabled('filters') && (bool) auth()->user()?->roleCan('gear_tags')),
                    ])->columns(2),
                ...VendorProfile::typeSections(),
                Section::make('Bank & transfers')
                    ->description('Account or InstaPay details used for payouts.')
                    ->schema(VendorProfile::bankFields())
                    ->columns(2),
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
                        Action::make('save')->label('Save profile')->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $vendor = $this->vendor();
        $state = $this->form->getState();
        $categoryIds = $state['category_ids'] ?? [];
        $filterTagIds = $state['filter_tag_ids'] ?? [];
        unset($state['category_ids'], $state['filter_tag_ids'], $state['vendor_type_id']);

        $incomingExtras = is_array($state['extras'] ?? null) ? $state['extras'] : [];
        $existingExtras = $vendor->extras ?? [];
        if (isset($existingExtras['prices'])) {
            $incomingExtras['prices'] = $existingExtras['prices'];
        }
        $state['extras'] = $incomingExtras;

        $vendor->update($state);
        $vendor->categories()->sync($categoryIds);

        if (Feature::enabled('filters') && auth()->user()?->roleCan('gear_tags')) {
            $vendor->filterTags()->sync($filterTagIds);
        }

        Notification::make()->title('Profile saved')->success()->send();
    }

    protected function vendor(): Vendor
    {
        $vendor = auth()->user()?->vendor;

        abort_unless($vendor, 403);

        return $vendor;
    }
}
