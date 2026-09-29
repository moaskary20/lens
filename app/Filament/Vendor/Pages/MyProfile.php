<?php

namespace App\Filament\Vendor\Pages;

use App\Filament\Vendor\Resources\MyPortfolioResource;
use App\Models\Category;
use App\Models\City;
use App\Models\FilterTag;
use App\Models\Vendor;
use App\Support\Feature;
use App\Support\Finance;
use App\Support\VendorProfile;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
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
        $vendor = $this->vendor()->loadMissing(['vendorType', 'categories', 'filterTags', 'badges', 'portfolios']);

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
            'address' => $vendor->address,
            'latitude' => $vendor->latitude,
            'longitude' => $vendor->longitude,
            'accepts_out_of_governorate' => $vendor->accepts_out_of_governorate,
            'default_travel_fee' => $vendor->default_travel_fee,
            'category_ids' => $vendor->categories()->pluck('categories.id')->all(),
            'filter_tag_ids' => $vendor->filterTags()->pluck('filter_tags.id')->all(),
            'profile_filters' => VendorProfile::hydrateProfileFilters($vendor),
            'specialties' => $vendor->specialties,
            'delivery_formats' => $vendor->delivery_formats,
            'turnaround_hours' => $vendor->turnaround_hours,
            'half_day_price' => $vendor->half_day_price,
            'full_day_price' => $vendor->full_day_price,
            'hourly_price' => $vendor->hourly_price,
            'per_video_price' => $vendor->per_video_price,
            'extras' => $vendor->extras,
            'payout_method' => $vendor->payout_method,
            'bank_name' => $vendor->bank_name,
            'bank_account_holder' => $vendor->bank_account_holder,
            'bank_account_number' => $vendor->bank_account_number,
            'bank_iban' => $vendor->bank_iban,
            'bank_swift' => $vendor->bank_swift,
            'bank_branch' => $vendor->bank_branch,
            'bank_branch_code' => $vendor->bank_branch_code,
            'bank_account_type' => $vendor->bank_account_type,
            'wallet_network_type' => $vendor->wallet_network_type,
            'wallet_telecom' => $vendor->wallet_telecom,
            'wallet_bank_name' => $vendor->wallet_bank_name,
            'wallet_phone' => $vendor->wallet_phone,
            'paypal_email' => $vendor->paypal_email,
            'paypal_name' => $vendor->paypal_name,
            'instapay' => $vendor->instapay,
            'transfer_notes' => $vendor->transfer_notes,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $vendor = $this->vendor()->loadMissing(['vendorType', 'badges']);

        return $schema
            ->model($vendor)
            ->components([
                Hidden::make('vendor_type_id'),
                Tabs::make('vendor')->tabs([
                    Tab::make('Identity')->schema([
                        Placeholder::make('vendor_type_label')
                            ->label('Vendor type')
                            ->content($vendor->vendorType?->name_en ?: 'Assigned by Lens admin')
                            ->helperText('Choosing a type is done by Lens staff. Matching filter-screen fields appear on Type profile.'),
                        TextInput::make('display_name')->label('Display name')->required(),
                        ...VendorProfile::personalFields(),
                        ...VendorProfile::contactFields(),
                        Select::make('city_id')->label('Home governorate')
                            ->options(fn (): array => City::query()->orderBy('name_en')->pluck('name_en', 'id')->all())
                            ->searchable()
                            ->preload(),
                        TextInput::make('address')->label('Address'),
                        TextInput::make('latitude')->label('Latitude')->numeric(),
                        TextInput::make('longitude')->label('Longitude')->numeric(),
                        FileUpload::make('cover_image')->label('Cover image')->image()->directory('vendors/covers')->columnSpanFull(),
                        Select::make('category_ids')
                            ->label('Services you offer')
                            ->multiple()
                            ->options(fn (): array => Category::query()->where('is_active', true)->orderBy('sort_order')->pluck('name_en', 'id')->all())
                            ->searchable()
                            ->preload(),
                        Select::make('filter_tag_ids')
                            ->label('Filter tags')
                            ->helperText('Optional extra tags. The Type profile tab is the same catalog as the mobile filter screens.')
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
                        Placeholder::make('badges_view')
                            ->label('Badges')
                            ->content($vendor->badges->pluck('name_en')->filter()->implode(', ') ?: 'No badges yet')
                            ->visible(fn (): bool => Feature::enabled('badges')),
                    ])->columns(2),
                    Tab::make('Type profile')->schema(VendorProfile::typeSections())->columns(1),
                    Tab::make('Previous projects')->schema([
                        VendorProfile::projectsRepeater()
                            ->helperText('Past jobs you already delivered — photos, reels, UGC clips, or social links.')
                            ->visible(fn (): bool => Feature::enabled('portfolio') && (bool) auth()->user()?->roleCan('build_portfolio')),
                        Placeholder::make('portfolio_desk')
                            ->hiddenLabel()
                            ->content('You can also manage the same gallery from Previous work in the sidebar.')
                            ->visible(fn (): bool => Feature::enabled('portfolio') && (bool) auth()->user()?->roleCan('build_portfolio')),
                    ]),
                    Tab::make('Pricing')->schema(VendorProfile::pricingFields())->columns(2),
                    Tab::make('Bank & payouts')->schema(VendorProfile::bankFields())->columns(2),
                    Tab::make('Verification & quality')->schema([
                        Placeholder::make('verification_status_label')
                            ->label('Verification')
                            ->content(match ($vendor->verification_status) {
                                'verified' => 'Verified — you can receive bookings.',
                                'rejected' => 'Rejected'.($vendor->verification_notes ? ': '.$vendor->verification_notes : '.'),
                                default => 'Pending review by Lens staff.',
                            })
                            ->helperText('Staff review your national ID and profile. You cannot change this status yourself.')
                            ->visible(fn (): bool => Feature::enabled('verification')),
                        Toggle::make('accepts_out_of_governorate')->label('Works outside home governorate')
                            ->helperText('If on, a travel fee is added when the booking city is in another governorate.')
                            ->live(),
                        TextInput::make('default_travel_fee')->label('Default transportation fee')->numeric()->prefix(Finance::currency())
                            ->helperText('Used when there is no specific rate for the destination governorate. Set per-governorate rates on Travel fees.')
                            ->visible(fn (Get $get): bool => (bool) $get('accepts_out_of_governorate')),
                        Placeholder::make('booked_sessions_view')->label('Sessions booked')->content((string) ($vendor->booked_sessions ?? 0)),
                        Placeholder::make('accepted_sessions_view')->label('Accepted')->content((string) ($vendor->accepted_sessions ?? 0)),
                        Placeholder::make('rejected_sessions_view')->label('Rejected')->content((string) ($vendor->rejected_sessions ?? 0)),
                        Placeholder::make('completed_sessions_view')->label('Completed')->content((string) ($vendor->completed_sessions ?? 0)),
                        Placeholder::make('failed_sessions_view')->label('Failed sessions')->content((string) ($vendor->failed_sessions ?? 0)),
                        Placeholder::make('penalty_total_view')->label('Applied penalties')->content(number_format((float) ($vendor->penalty_total ?? 0), 0).' '.Finance::currency()),
                        Placeholder::make('rating_avg_view')->label('Average rating')->content(number_format((float) ($vendor->rating_avg ?? 0), 2).' ★'),
                        Placeholder::make('rating_count_view')->label('Review count')->content((string) ($vendor->rating_count ?? 0)),
                    ])->columns(2),
                ])->persistTabInQueryString()->columnSpanFull(),
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
                        Action::make('openPortfolio')
                            ->label('Open previous work')
                            ->url(MyPortfolioResource::getUrl('index'))
                            ->color('gray')
                            ->visible(fn (): bool => Feature::enabled('portfolio') && (bool) auth()->user()?->roleCan('build_portfolio')),
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
        $profileFilters = is_array($state['profile_filters'] ?? null) ? $state['profile_filters'] : [];
        unset(
            $state['category_ids'],
            $state['filter_tag_ids'],
            $state['profile_filters'],
            $state['vendor_type_id'],
            $state['portfolios'],
            $state['verification_status'],
            $state['verification_notes'],
            $state['is_active'],
            $state['is_featured'],
            $state['user_id'],
            $state['booked_sessions'],
            $state['accepted_sessions'],
            $state['rejected_sessions'],
            $state['completed_sessions'],
            $state['failed_sessions'],
            $state['penalty_total'],
            $state['rating_avg'],
            $state['rating_count'],
            $state['response_minutes'],
        );

        $incomingExtras = is_array($state['extras'] ?? null) ? $state['extras'] : [];
        $existingExtras = $vendor->extras ?? [];
        $state['extras'] = array_replace_recursive($existingExtras, $incomingExtras);

        $vendor->update($state);
        $vendor->categories()->sync($categoryIds);

        $extraIds = Feature::enabled('filters') && auth()->user()?->roleCan('gear_tags')
            ? $filterTagIds
            : [];
        VendorProfile::syncProfileFilters($vendor, $profileFilters, $extraIds);

        $this->form->model($vendor->fresh())->saveRelationships();

        Notification::make()->title('Profile saved')->success()->send();
    }

    protected function vendor(): Vendor
    {
        $vendor = auth()->user()?->vendor;

        abort_unless($vendor, 403);

        return $vendor;
    }
}
