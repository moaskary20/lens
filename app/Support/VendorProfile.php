<?php

namespace App\Support;

use App\Models\City;
use App\Models\FilterGroup;
use App\Models\PricingModelField;
use App\Models\Vendor;
use App\Models\VendorType;
use Carbon\Carbon;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class VendorProfile
{
    /**
     * @var list<string>
     */
    public const GEAR_EXTRA_KEYS = [
        'cameras',
        'lenses',
        'lighting',
        'camera_kit',
        'stabilization',
        'devices',
    ];

    public static function slug(Get $get, ?Vendor $record = null): ?string
    {
        $id = $get('vendor_type_id')
            ?? $get('../../vendor_type_id')
            ?? $record?->vendor_type_id;

        return self::slugFromId($id);
    }

    public static function slugFromId(int|string|null $id): ?string
    {
        if (! $id) {
            return null;
        }

        return VendorType::query()->whereKey($id)->value('slug');
    }

    public static function is(Get $get, ?Vendor $record, string ...$slugs): bool
    {
        $slug = self::slug($get, $record);

        return $slug !== null && in_array($slug, $slugs, true);
    }

    public static function calendarTitle(?string $slug): string
    {
        return match ($slug) {
            'studio' => 'Hourly slot booking calendar',
            'model' => 'Availability calendar',
            default => 'Calendar availability',
        };
    }

    /**
     * @return \Closure(Get, Vendor|null): bool
     */
    public static function visibleFor(string ...$slugs): \Closure
    {
        return fn (Get $get, ?Vendor $record = null): bool => self::is($get, $record, ...$slugs);
    }

    /**
     * @return array<string, string>
     */
    public static function specialtyOptions(): array
    {
        return [
            'wedding' => 'Wedding',
            'product' => 'Product',
            'editorial' => 'Editorial',
            'corporate' => 'Corporate',
            'fnb' => 'Food & drinks',
            'events' => 'Events',
            'photosession' => 'Personal photoshoot',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function studioRooms(): array
    {
        return [
            'bedroom' => 'Bedroom set',
            'kitchen' => 'Full working kitchen',
            'cyclorama' => 'Cyclorama wall',
            'green_screen' => 'Chroma green screen',
            'brick' => 'Industrial brick wall',
            'podcast' => 'Podcast room',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function foodAddons(): array
    {
        return [
            'recipe_development' => 'Recipe development',
            'prop_sourcing' => 'Prop sourcing',
            'food_staging' => 'Food styling & staging',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function modelExperience(): array
    {
        return [
            'commercial' => 'Commercial & TV ads',
            'fashion' => 'Fashion & lookbooks',
            'editorial' => 'Editorial & magazine',
            'ecommerce' => 'E-commerce',
            'actor' => 'Actor',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function deliveryFormatOptions(): array
    {
        return [
            '4K' => '4K',
            'ProRes' => 'ProRes',
            'MP4' => 'MP4',
            'Log' => 'Log / Rec.709',
        ];
    }

    /**
     * Filter groups shown on mobile filter screens that a vendor should answer.
     *
     * @return list<string>
     */
    public static function profileSkipSlugs(): array
    {
        return [
            'project_type',
            'location',
            'availability',
            'budget',
            'rating',
            'search_cities',
            'studio_hourly',
            'ugc_price',
            'food_price',
            'model_sizes',
            'model_experience',
            'ugc',
            'food_stylist',
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, FilterGroup>
     */
    public static function filterProfileGroups(?string $slug = null)
    {
        return FilterGroup::query()
            ->where('is_active', true)
            ->whereNotIn('slug', self::profileSkipSlugs())
            ->when(
                $slug,
                fn ($query) => $query->whereJsonContains('vendor_type_slugs', $slug),
                fn ($query) => $query->whereJsonLength('vendor_type_slugs', '>', 0),
            )
            ->with(['options' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (FilterGroup $group): bool => $group->options->isNotEmpty())
            ->values();
    }

    /**
     * @return list<\Filament\Schemas\Components\Component>
     */
    public static function filterProfileFields(): array
    {
        $fields = [];

        foreach (self::filterProfileGroups() as $group) {
            $typeSlugs = array_values(array_filter($group->vendor_type_slugs ?? []));
            if ($typeSlugs === []) {
                continue;
            }

            $fields[] = CheckboxList::make('profile_filters.'.$group->slug)
                ->label($group->name)
                ->helperText($group->description ?: 'Same options clients use on the mobile filter screen.')
                ->options($group->options->pluck('name_en', 'id'))
                ->columns(2)
                ->columnSpanFull()
                ->visible(self::visibleFor(...$typeSlugs));
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $profileFilters
     * @return list<int>
     */
    public static function flattenProfileFilters(array $profileFilters): array
    {
        return collect($profileFilters)
            ->flatten()
            ->filter(fn ($id): bool => is_numeric($id))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, list<int>>
     */
    public static function hydrateProfileFilters(?Vendor $vendor): array
    {
        if (! $vendor) {
            return [];
        }

        $grouped = [];
        foreach ($vendor->filterTags()->with('group')->get() as $tag) {
            $slug = $tag->group?->slug ?: $tag->group_key;
            if (! $slug) {
                continue;
            }
            $grouped[$slug][] = $tag->id;
        }

        return $grouped;
    }

    /**
     * @param  array<string, mixed>  $profileFilters
     */
    public static function syncProfileFilters(Vendor $vendor, array $profileFilters, array $extraIds = []): void
    {
        $ids = array_values(array_unique(array_merge(
            self::flattenProfileFilters($profileFilters),
            array_map('intval', $extraIds),
        )));

        $vendor->filterTags()->sync($ids);
    }

    /**
     * @return list<\Filament\Schemas\Components\Component>
     */
    public static function typeSections(): array
    {
        return [
            Placeholder::make('pick_vendor_type')
                ->hiddenLabel()
                ->content('Choose a vendor type on the Identity tab. Matching filter-screen fields will appear here.')
                ->visible(fn (Get $get, ?Vendor $record = null): bool => self::slug($get, $record) === null),

            Section::make('Filter profile')
                ->description('The same options clients use on the mobile filter screens. These tags decide how the vendor appears in search.')
                ->schema(self::filterProfileFields())
                ->visible(fn (Get $get, ?Vendor $record = null): bool => self::slug($get, $record) !== null),

            Section::make('Photographer extras')
                ->description('Free-text notes that sit next to the filter tags.')
                ->visible(self::visibleFor('photographer'))
                ->schema([
                    CheckboxList::make('specialties')->label('Primary specialties')
                        ->options(self::specialtyOptions())
                        ->columns(2)
                        ->columnSpanFull(),
                    Placeholder::make('photographer_projects_hint')
                        ->hiddenLabel()
                        ->content('Add a photo portfolio grid under Previous projects (images of completed shoots).')
                        ->columnSpanFull(),
                ]),

            Section::make('Videographer extras')
                ->visible(self::visibleFor('videographer'))
                ->schema([
                    CheckboxList::make('delivery_formats')->label('Supported delivery formats')
                        ->options(self::deliveryFormatOptions())
                        ->columns(2)
                        ->columnSpanFull(),
                    Placeholder::make('videographer_projects_hint')
                        ->hiddenLabel()
                        ->content('Add video reel highlights under Previous projects (upload clips or paste a reel URL).')
                        ->columnSpanFull(),
                ]),

            Section::make('Mobile Reels extras')
                ->visible(self::visibleFor('reels'))
                ->schema([
                    TextInput::make('extras.tiktok_url')->label('TikTok sample link')->url()->columnSpanFull(),
                    TextInput::make('extras.instagram_url')->label('Instagram / Reels sample link')->url()->columnSpanFull(),
                    TextInput::make('extras.standard_session_hours')->label('Standard session length (hours)')->numeric()->minValue(0.5)->step(0.5),
                    Placeholder::make('reels_projects_hint')
                        ->hiddenLabel()
                        ->content('You can also attach more TikTok / Instagram links as Previous projects of type Link.')
                        ->columnSpanFull(),
                ]),

            Section::make('Studio extras')
                ->visible(self::visibleFor('studio'))
                ->schema([
                    Placeholder::make('studio_calendar_hint')
                        ->hiddenLabel()
                        ->content('Add the studio gallery under Previous projects. After saving, book hourly slots from the Hourly slot booking calendar tab.')
                        ->columnSpanFull(),
                ]),

            Section::make('Model extras')
                ->visible(self::visibleFor('model'))
                ->schema([
                    TextInput::make('extras.hair_color')->label('Hair color'),
                    TextInput::make('extras.eye_color')->label('Eye color'),
                    Placeholder::make('model_projects_hint')
                        ->hiddenLabel()
                        ->content('Upload a polished photo portfolio under Previous projects. After saving, set open dates on the Availability calendar tab.')
                        ->columnSpanFull(),
                ])->columns(2),

            Section::make('UGC extras')
                ->visible(self::visibleFor('ugc'))
                ->schema([
                    Placeholder::make('ugc_projects_hint')
                        ->hiddenLabel()
                        ->content('Add short-form UGC video samples under Previous projects (vertical video files or social links).')
                        ->columnSpanFull(),
                ]),

            Section::make('Food Stylist extras')
                ->visible(self::visibleFor('food_stylist'))
                ->schema([
                    Textarea::make('extras.addon_notes')->label('Add-on notes')->rows(3)->columnSpanFull(),
                    Placeholder::make('food_projects_hint')
                        ->hiddenLabel()
                        ->content('Add a food styling & staging portfolio under Previous projects.')
                        ->columnSpanFull(),
                ]),
        ];
    }

    public static function pricingModelId(Get $get, ?Vendor $record = null): ?int
    {
        $typeId = $get('vendor_type_id') ?: $record?->vendor_type_id;

        if (! $typeId) {
            return $record?->vendorType?->pricing_model_id;
        }

        return VendorType::query()->whereKey($typeId)->value('pricing_model_id');
    }

    /**
     * @return list<\Filament\Schemas\Components\Component>
     */
    public static function pricingFields(): array
    {
        $fields = [
            Placeholder::make('pick_type_for_pricing')
                ->hiddenLabel()
                ->content('Choose a vendor type first. The admin-defined price fields for that type will appear here. You set the amounts; admin sets the logic.')
                ->visible(fn (Get $get, ?Vendor $record = null): bool => ! self::pricingModelId($get, $record)),
        ];

        foreach (PricingModelField::query()->orderBy('pricing_model_id')->orderBy('sort_order')->get() as $field) {
            $modelId = $field->pricing_model_id;
            $fields[] = TextInput::make($field->statePath())
                ->label($field->label)
                ->helperText($field->helper_text)
                ->numeric()
                ->prefix(Finance::currency())
                ->minValue(0)
                ->visible(fn (Get $get, ?Vendor $record = null): bool => (int) self::pricingModelId($get, $record) === (int) $modelId);
        }

        $fields[] = TextInput::make('turnaround_hours')->label('Post-production turnaround (hours)')->numeric()->minValue(1)
            ->visible(self::visibleFor('videographer', 'reels', 'ugc', 'photographer'));

        return $fields;
    }

    public static function projectsRepeater(): Repeater
    {
        $normalizePath = function (array $data): array {
            $data['path'] = filled($data['path'] ?? null) ? $data['path'] : '';

            return $data;
        };

        return Repeater::make('portfolios')
            ->relationship()
            ->label('Completed projects')
            ->addActionLabel('Add previous project')
            ->defaultItems(0)
            ->collapsed()
            ->cloneable()
            ->orderColumn('sort_order')
            ->schema([
                Select::make('type')->label('Item type')->options([
                    'image' => 'Photo',
                    'video' => 'Video',
                    'link' => 'Social / external link',
                ])->default('image')->live()->required(),
                TextInput::make('title')->label('Project title')->required(),
                TextInput::make('completed_on')->label('Completed on')->placeholder('2025 or Jun 2025'),
                FileUpload::make('path')->label('Photo / video file')
                    ->directory('portfolios')
                    ->visible(fn (Get $get): bool => in_array($get('type'), ['image', 'video'], true))
                    ->acceptedFileTypes(fn (Get $get): array => $get('type') === 'video'
                        ? ['video/mp4', 'video/quicktime', 'video/webm']
                        : ['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(fn ($record = null): int => \App\Support\StorageQuota::portfolioMaxSizeKb(
                        $record instanceof \App\Models\Vendor
                            ? $record->vendorType?->slug
                            : ($record instanceof \App\Models\Portfolio
                                ? $record->vendor?->vendorType?->slug
                                : auth()->user()?->vendor?->vendorType?->slug)
                    ))
                    ->helperText(fn ($record = null): string => 'This vendor category may store up to '.\App\Support\StorageQuota::portfolioQuotaMb(
                        $record instanceof \App\Models\Vendor
                            ? $record->vendorType?->slug
                            : ($record instanceof \App\Models\Portfolio
                                ? $record->vendor?->vendorType?->slug
                                : auth()->user()?->vendor?->vendorType?->slug)
                    ).' MB of portfolio files.'),
                TextInput::make('external_url')->label('Sample URL')
                    ->url()
                    ->visible(fn (Get $get): bool => in_array($get('type'), ['link', 'video'], true))
                    ->required(fn (Get $get): bool => $get('type') === 'link'),
                Textarea::make('description')->label('What was delivered')->rows(3)->columnSpanFull(),
                Toggle::make('is_featured')->label('Feature this project'),
            ])
            ->columns(2)
            ->columnSpanFull()
            ->mutateRelationshipDataBeforeCreateUsing($normalizePath)
            ->mutateRelationshipDataBeforeSaveUsing($normalizePath);
    }

    /**
     * @return list<\Filament\Schemas\Components\Component>
     */
    public static function personalFields(): array
    {
        return [
            FileUpload::make('profile_photo')->label('Personal photo')->image()->directory('vendors/photos')->avatar(),
            FileUpload::make('national_id_image')->label('National ID card')->image()->directory('vendors/ids')
                ->helperText('Front of the Egyptian national ID for verification.'),
            DatePicker::make('date_of_birth')->label('Date of birth')->native(false)->maxDate(now()->subYears(16))->live(),
            Placeholder::make('age_years')->label('Age')
                ->content(function (Get $get, ?Vendor $record = null): string {
                    $dob = $get('date_of_birth') ?: $record?->date_of_birth;
                    if (! $dob) {
                        return 'Set date of birth';
                    }

                    return Carbon::parse($dob)->age.' years';
                }),
            TextInput::make('profession')->label('Profession / job title')
                ->helperText('Free text. Marketplace category is the vendor type (photographer, studio, …).'),
            Textarea::make('bio')->label('About')->rows(3)->columnSpanFull(),
        ];
    }

    /**
     * @return list<\Filament\Schemas\Components\Component>
     */
    public static function contactFields(): array
    {
        return [
            TextInput::make('contact_phone')->label('Phone')->tel()
                ->rule(Egypt::mobileRule())
                ->validationMessages(['regex' => Egypt::mobileMessage()])
                ->helperText(Egypt::mobileMessage()),
            TextInput::make('contact_email')->label('Email')->email(),
            TextInput::make('whatsapp')->label('WhatsApp')->tel()
                ->rule(Egypt::mobileRule())
                ->validationMessages(['regex' => Egypt::mobileMessage()]),
            TextInput::make('instagram')->label('Instagram')->placeholder('@studio'),
        ];
    }

    /**
     * @return list<\Filament\Schemas\Components\Component>
     */
    public static function bankFields(): array
    {
        return [
            ToggleButtons::make('payout_method')
                ->label('Payout method')
                ->options([
                    'bank' => 'Bank account',
                    'wallet' => 'Mobile wallet',
                    'paypal' => 'PayPal',
                ])
                ->icons([
                    'bank' => 'heroicon-o-building-library',
                    'wallet' => 'heroicon-o-device-phone-mobile',
                    'paypal' => 'heroicon-o-globe-alt',
                ])
                ->colors([
                    'bank' => 'primary',
                    'wallet' => 'warning',
                    'paypal' => 'info',
                ])
                ->inline()
                ->grouped()
                ->live()
                ->columnSpanFull(),
            Section::make('Bank account')
                ->description('Full bank details used to send payouts to this vendor.')
                ->visible(fn (Get $get): bool => $get('payout_method') === 'bank')
                ->schema([
                    Select::make('bank_name')
                        ->label('Bank')
                        ->options(Egypt::banks())
                        ->searchable()
                        ->required(fn (Get $get): bool => $get('payout_method') === 'bank'),
                    Select::make('bank_account_type')
                        ->label('Account type')
                        ->options([
                            'current' => 'Current',
                            'savings' => 'Savings',
                        ])
                        ->native(false),
                    TextInput::make('bank_account_holder')
                        ->label('Account holder name')
                        ->required(fn (Get $get): bool => $get('payout_method') === 'bank'),
                    TextInput::make('bank_account_number')
                        ->label('Account number')
                        ->required(fn (Get $get): bool => $get('payout_method') === 'bank'),
                    TextInput::make('bank_iban')
                        ->label('IBAN')
                        ->placeholder('EG00 ACCT-000003 0000 000'),
                    TextInput::make('bank_swift')
                        ->label('SWIFT / BIC')
                        ->placeholder('BMISEGCXXXX'),
                    TextInput::make('bank_branch')
                        ->label('Branch name'),
                    TextInput::make('bank_branch_code')
                        ->label('Branch code'),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Mobile wallet')
                ->description('Add the phone number linked to the wallet, then choose an Egyptian telecom company or a bank.')
                ->visible(fn (Get $get): bool => $get('payout_method') === 'wallet')
                ->schema([
                    TextInput::make('wallet_phone')
                        ->label('Wallet phone number')
                        ->tel()
                        ->placeholder('010xxxxxxxx')
                        ->helperText('Egyptian mobile number linked to this wallet.')
                        ->rule(Egypt::mobileRule())
                        ->validationMessages([
                            'regex' => Egypt::mobileMessage(),
                        ])
                        ->required(fn (Get $get): bool => $get('payout_method') === 'wallet')
                        ->columnSpanFull(),
                    Radio::make('wallet_network_type')
                        ->label('Wallet issued by')
                        ->options([
                            'telecom' => 'Egyptian telecom company',
                            'bank' => 'Bank',
                        ])
                        ->inline()
                        ->live()
                        ->required(fn (Get $get): bool => $get('payout_method') === 'wallet')
                        ->columnSpanFull(),
                    Select::make('wallet_telecom')
                        ->label('Telecom company')
                        ->options(Egypt::telecomWallets())
                        ->native(false)
                        ->required(fn (Get $get): bool => $get('payout_method') === 'wallet' && $get('wallet_network_type') === 'telecom')
                        ->visible(fn (Get $get): bool => $get('wallet_network_type') === 'telecom'),
                    Select::make('wallet_bank_name')
                        ->label('Bank')
                        ->options(Egypt::banks())
                        ->searchable()
                        ->required(fn (Get $get): bool => $get('payout_method') === 'wallet' && $get('wallet_network_type') === 'bank')
                        ->visible(fn (Get $get): bool => $get('wallet_network_type') === 'bank'),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('PayPal')
                ->description('Send payouts to the vendor PayPal account.')
                ->visible(fn (Get $get): bool => $get('payout_method') === 'paypal')
                ->schema([
                    TextInput::make('paypal_email')
                        ->label('PayPal email')
                        ->email()
                        ->required(fn (Get $get): bool => $get('payout_method') === 'paypal'),
                    TextInput::make('paypal_name')
                        ->label('PayPal account name'),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Textarea::make('transfer_notes')
                ->label('Other transfer details')
                ->rows(3)
                ->visible(fn (Get $get): bool => filled($get('payout_method')))
                ->columnSpanFull(),
        ];
    }

    /**
     * @return list<\Filament\Schemas\Components\Component>
     */
    public static function workAreaFields(): array
    {
        return [
            Select::make('city_id')->label('Home governorate')
                ->options(fn (): array => City::query()->orderBy('name_en')->pluck('name_en', 'id')->all())
                ->searchable()
                ->preload(),
            Toggle::make('accepts_out_of_governorate')->label('Works outside home governorate')->live(),
            TextInput::make('default_travel_fee')->label('Default transportation fee')->numeric()->prefix(Finance::currency())
                ->visible(fn (Get $get): bool => (bool) $get('accepts_out_of_governorate')),
        ];
    }
}
