<?php

namespace App\Models;

use App\Support\Roles;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'avatar',
        'is_active',
        'locale',
        'city_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return match ($panel->getId()) {
            'admin' => $this->isStaff(),
            'vendor' => $this->isVendor() && $this->vendor()->exists(),
            default => false,
        };
    }

    public function isAdmin(): bool
    {
        return $this->role === Roles::ADMIN;
    }

    public function isSupervisor(): bool
    {
        return $this->role === Roles::SUPERVISOR;
    }

    public function isStaff(): bool
    {
        return $this->isAdmin() || $this->isSupervisor();
    }

    public function isClient(): bool
    {
        return $this->role === Roles::CLIENT;
    }

    public function isVendor(): bool
    {
        return $this->role === Roles::VENDOR;
    }

    public function vendorId(): int
    {
        return (int) ($this->vendor?->id ?: 0);
    }

    public function ownsVendor(?Vendor $vendor): bool
    {
        return $this->isVendor() && $vendor !== null && $this->vendorId() === (int) $vendor->id;
    }

    public function staffCan(string $capability): bool
    {
        return Roles::staffCan($this, $capability);
    }

    public function roleCan(string $capability): bool
    {
        return Roles::roleCan($this, $capability);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function vendor(): HasOne
    {
        return $this->hasOne(Vendor::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'client_id');
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    public function assignedCoupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class, 'coupon_user');
    }

    public function recommendationSignals(): HasMany
    {
        return $this->hasMany(RecommendationSignal::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoriteVendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class, 'favorites')->withTimestamps();
    }

    protected static function booted(): void
    {
        static::created(function (User $user): void {
            app(\App\Services\WalletService::class)->ensure($user);

            if (in_array($user->role, ['client', 'vendor'], true)) {
                \App\Support\LensNotifier::staff(
                    \App\Support\LensNotifier::ACCOUNT_REGISTERED,
                    'New account',
                    $user->name.' registered as '.$user->role.'.',
                    $user->id,
                );
                \App\Support\LensNotifier::toUser(
                    $user,
                    \App\Support\LensNotifier::ACCOUNT_REGISTERED,
                    'Welcome to Lens',
                    'Your '.$user->role.' account is ready.',
                );
            }
        });
    }
}
