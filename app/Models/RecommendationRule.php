<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendationRule extends Model
{
    protected $fillable = [
        'name', 'trigger_type', 'trigger_value', 'suggest_vendor_type_id',
        'suggest_filter_tag_ids', 'suggest_message', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'suggest_filter_tag_ids' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function suggestedVendorType(): BelongsTo
    {
        return $this->belongsTo(VendorType::class, 'suggest_vendor_type_id');
    }
}
