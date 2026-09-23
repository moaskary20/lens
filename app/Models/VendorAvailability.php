<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VendorAvailability extends Model
{
    protected $fillable = [
        'vendor_id', 'starts_at', 'ends_at', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class, 'availability_id');
    }

    public function isSelectable(): bool
    {
        return $this->status === 'open';
    }
}
