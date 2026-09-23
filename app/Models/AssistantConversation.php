<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AssistantConversation extends Model
{
    protected $fillable = [
        'uuid', 'user_id', 'locale', 'status', 'slots',
    ];

    protected function casts(): array
    {
        return [
            'slots' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AssistantConversation $conversation): void {
            $conversation->uuid ??= (string) Str::uuid();
            $conversation->slots ??= [];
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AssistantMessage::class)->orderBy('id');
    }

    /**
     * @return array<string, mixed>
     */
    public function slotBag(): array
    {
        return is_array($this->slots) ? $this->slots : [];
    }
}
