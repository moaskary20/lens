<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPaymentMethod extends Model
{
    protected $fillable = [
        'user_id', 'type', 'label', 'details', 'is_default',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
