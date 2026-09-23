<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppScreen extends Model
{
    protected $fillable = [
        'slug', 'title', 'body', 'image', 'cta_label', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
