<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReplacementOffer extends Model
{
    protected $fillable = [
        'booking_id', 'original_vendor_id', 'suggested_vendor_id', 'status', 'notes',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function originalVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'original_vendor_id');
    }

    public function suggestedVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'suggested_vendor_id');
    }
}
