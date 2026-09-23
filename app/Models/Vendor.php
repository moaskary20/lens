<?php

namespace App\Models;

use App\Support\VendorProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    protected $fillable = [
        'user_id', 'vendor_type_id', 'city_id', 'display_name', 'bio', 'cover_image',
        'address', 'latitude', 'longitude', 'verification_status', 'verification_notes',
        'is_active', 'is_featured', 'accepts_out_of_governorate', 'default_travel_fee',
        'profile_photo', 'national_id_image', 'date_of_birth', 'profession',
        'contact_phone', 'contact_email', 'whatsapp', 'instagram',
        'bank_name', 'bank_account_holder', 'bank_account_number', 'bank_iban', 'instapay', 'transfer_notes',
        'half_day_price', 'full_day_price', 'hourly_price',
        'per_video_price', 'turnaround_hours', 'delivery_formats', 'equipment',
        'specialties', 'extras', 'booked_sessions', 'completed_sessions',
        'accepted_sessions', 'rejected_sessions', 'failed_sessions', 'penalty_total',
        'rating_avg', 'rating_count', 'response_minutes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'accepts_out_of_governorate' => 'boolean',
            'date_of_birth' => 'date',
            'default_travel_fee' => 'decimal:2',
            'delivery_formats' => 'array',
            'equipment' => 'array',
            'specialties' => 'array',
            'extras' => 'array',
            'half_day_price' => 'decimal:2',
            'full_day_price' => 'decimal:2',
            'hourly_price' => 'decimal:2',
            'per_video_price' => 'decimal:2',
            'penalty_total' => 'decimal:2',
            'rating_avg' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Vendor $vendor): void {
            $vendor->syncEquipmentFromExtras();
        });

        static::updated(function (Vendor $vendor): void {
            if (! $vendor->wasChanged('verification_status')) {
                return;
            }

            $vendor->loadMissing('user');

            if ($vendor->verification_status === 'verified') {
                \App\Support\LensNotifier::vendor(
                    $vendor,
                    \App\Support\LensNotifier::ACCOUNT_APPROVED,
                    'Account approved',
                    'Your Lens vendor account was verified. You can receive bookings.',
                );
            }

            if ($vendor->verification_status === 'rejected') {
                \App\Support\LensNotifier::vendor(
                    $vendor,
                    \App\Support\LensNotifier::ACCOUNT_REJECTED,
                    'Account rejected',
                    'Your Lens vendor verification was rejected'.($vendor->verification_notes ? ': '.$vendor->verification_notes : '.'),
                );
            }
        });
    }

    public function syncEquipmentFromExtras(): void
    {
        $extras = $this->extras ?? [];
        $fromExtras = [];

        foreach (VendorProfile::GEAR_EXTRA_KEYS as $key) {
            if (! empty($extras[$key]) && is_array($extras[$key])) {
                $fromExtras = array_merge($fromExtras, $extras[$key]);
            }
        }

        if ($fromExtras !== []) {
            $this->equipment = array_values(array_unique($fromExtras));
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vendorType(): BelongsTo
    {
        return $this->belongsTo(VendorType::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function filterTags(): BelongsToMany
    {
        return $this->belongsToMany(FilterTag::class, 'vendor_filter_tag');
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class);
    }

    public function portfolios(): HasMany
    {
        return $this->hasMany(Portfolio::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(VendorAvailability::class);
    }

    public function travelRates(): HasMany
    {
        return $this->hasMany(VendorTravelRate::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function fans(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function age(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function resolvedPricingModel(): ?PricingModel
    {
        $this->loadMissing('vendorType.pricingModel.fields');

        return $this->vendorType?->pricingModel;
    }

    public function pricingFieldFor(?string $packageType): ?PricingModelField
    {
        if (! $packageType) {
            return null;
        }

        return $this->resolvedPricingModel()
            ?->fields
            ->first(fn (PricingModelField $field): bool => $field->package_type === $packageType);
    }
}
