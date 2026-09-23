<?php

namespace App\Models;

use App\Services\SlotService;
use App\Support\StorageQuota;
use App\Support\VendorNotifier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\ValidationException;

class Booking extends Model
{
    protected $fillable = [
        'reference', 'client_id', 'vendor_id', 'category_id', 'status', 'scheduled_at',
        'availability_id',
        'duration_hours', 'package_type', 'location_text', 'city_id', 'session_price',
        'travel_fee', 'client_fee',
        'tax_amount', 'total_paid', 'discount_amount', 'coupon_id', 'vendor_commission', 'vendor_net', 'escrow_status',
        'payout_status', 'checked_in_at', 'approved_at', 'cancelled_at', 'cancelled_by',
        'cancellation_reason', 'revision_count', 'notes', 'client_brief', 'client_project_files',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'session_price' => 'decimal:2',
            'travel_fee' => 'decimal:2',
            'client_fee' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_paid' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'vendor_commission' => 'decimal:2',
            'vendor_net' => 'decimal:2',
            'client_project_files' => 'array',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function availability(): BelongsTo
    {
        return $this->belongsTo(VendorAvailability::class, 'availability_id');
    }

    public function durationHours(): int
    {
        if ($this->duration_hours) {
            return max(1, (int) $this->duration_hours);
        }

        return match ($this->package_type) {
            'hourly' => 1,
            'full_day' => 12,
            'per_video' => 2,
            default => 6,
        };
    }

    public function occupiesSlot(): bool
    {
        return in_array($this->status, SlotService::occupyingStatuses(), true);
    }

    protected static function booted(): void
    {
        static::created(function (Booking $booking): void {
            if ($booking->status === 'pending') {
                $booking->loadMissing(['vendor.user', 'client']);
                VendorNotifier::send(
                    $booking->vendor,
                    'New client request',
                    ($booking->client?->name ?: 'A client').' sent request '.$booking->reference.'. Open Incoming requests to accept or reject.',
                    \App\Support\LensNotifier::BOOKING_CREATED,
                );
                \App\Support\LensNotifier::staff(
                    \App\Support\LensNotifier::BOOKING_CREATED,
                    'New request',
                    ($booking->client?->name ?: 'A client').' sent '.$booking->reference.'.',
                );
            }

            app(\App\Services\RecommendationService::class)->recordFromBooking($booking);
        });

        static::saving(function (Booking $booking): void {
            app(SlotService::class)->guard($booking);

            if ($booking->isDirty('client_project_files')) {
                $booking->loadMissing('deliverables');
                $cap = StorageQuota::clientProjectQuotaMb() * 1024 * 1024;
                if (StorageQuota::bookingProjectBytes($booking) > $cap) {
                    throw ValidationException::withMessages([
                        'client_project_files' => 'This client project allows '.StorageQuota::clientProjectQuotaMb().' MB of files.',
                    ]);
                }
            }
        });

        static::saved(function (Booking $booking): void {
            app(SlotService::class)->sync($booking);

            if ($booking->wasChanged('status') && $booking->status === 'in_revision') {
                $booking->loadMissing('vendor.user');
                VendorNotifier::send(
                    $booking->vendor,
                    'Revision requested',
                    'The client asked for edits on '.$booking->reference.'. Upload a revised file from Incoming requests.',
                    \App\Support\LensNotifier::BOOKING_STATUS,
                );
            }

            if ($booking->wasChanged('status') && ! $booking->wasRecentlyCreated) {
                $booking->loadMissing(['client', 'vendor.user']);
                $status = $booking->status;

                if ($status === 'accepted') {
                    \App\Support\LensNotifier::toUser(
                        $booking->client,
                        \App\Support\LensNotifier::BOOKING_ACCEPTED,
                        'Request accepted',
                        $booking->vendor?->display_name.' accepted '.$booking->reference.'.',
                    );
                } elseif ($status === 'rejected') {
                    \App\Support\LensNotifier::toUser(
                        $booking->client,
                        \App\Support\LensNotifier::BOOKING_STATUS,
                        'Request declined',
                        $booking->vendor?->display_name.' declined '.$booking->reference.'.',
                    );
                } elseif ($status === 'cancelled') {
                    $body = $booking->reference.' was cancelled'.($booking->cancelled_by ? ' by the '.$booking->cancelled_by : '').'.';
                    \App\Support\LensNotifier::toUser($booking->client, \App\Support\LensNotifier::BOOKING_CANCELLED, 'Booking cancelled', $body);
                    \App\Support\LensNotifier::vendor($booking->vendor, \App\Support\LensNotifier::BOOKING_CANCELLED, 'Booking cancelled', $body);
                } elseif (! in_array($status, ['in_revision', 'pending'], true)) {
                    $label = str_replace('_', ' ', $status);
                    $body = $booking->reference.' is now '.$label.'.';
                    \App\Support\LensNotifier::toUser($booking->client, \App\Support\LensNotifier::BOOKING_STATUS, 'Session status updated', $body);
                    \App\Support\LensNotifier::vendor($booking->vendor, \App\Support\LensNotifier::BOOKING_STATUS, 'Session status updated', $body);
                }
            }
        });
    }

    public function escrowTransactions(): HasMany
    {
        return $this->hasMany(EscrowTransaction::class);
    }

    public function dispute(): HasOne
    {
        return $this->hasOne(Dispute::class);
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(Deliverable::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function replacementOffers(): HasMany
    {
        return $this->hasMany(ReplacementOffer::class);
    }

    public function moodboard(): HasOne
    {
        return $this->hasOne(Moodboard::class);
    }

    /**
     * @return list<string>
     */
    public function clientProjectPaths(): array
    {
        $files = $this->client_project_files ?? [];
        if (! is_array($files)) {
            return [];
        }

        return array_values(array_filter($files, fn ($path): bool => is_string($path) && $path !== ''));
    }

    public function isClosedForStorage(): bool
    {
        return in_array($this->status, ['completed', 'approved', 'cancelled', 'rejected', 'failed', 'refunded'], true);
    }

    public function isCancellable(): bool
    {
        return ! in_array($this->status, ['cancelled', 'failed', 'approved', 'completed', 'refunded'], true);
    }
}
