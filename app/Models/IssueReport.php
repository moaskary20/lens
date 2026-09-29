<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueReport extends Model
{
    public const TOPICS = [
        'booking' => 'Booking',
        'payment' => 'Payment',
        'account' => 'Account',
        'app' => 'App bug',
        'creator' => 'Creator',
        'other' => 'Other',
    ];

    public const STATUSES = [
        'open' => 'Open',
        'reviewing' => 'In review',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ];

    protected $fillable = [
        'reference',
        'user_id',
        'name',
        'email',
        'phone',
        'topic',
        'subject',
        'body',
        'booking_reference',
        'app_version',
        'platform',
        'status',
        'admin_notes',
        'staff_reply',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (IssueReport $issue): void {
            if (filled($issue->reference)) {
                return;
            }

            $issue->forceFill([
                'reference' => 'ISS-'.str_pad((string) $issue->id, 4, '0', STR_PAD_LEFT),
            ])->saveQuietly();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topicLabel(): string
    {
        return self::TOPICS[$this->topic] ?? ucfirst((string) $this->topic);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'reviewing'], true);
    }
}
