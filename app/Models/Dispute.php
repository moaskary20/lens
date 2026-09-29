<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispute extends Model
{
    protected $fillable = [
        'booking_id', 'opened_by', 'status', 'kind', 'decision', 'reason', 'client_refund_percent',
        'vendor_payout_percent', 'platform_fee_percent', 'admin_notes',
        'resolved_at', 'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'client_refund_percent' => 'decimal:2',
            'vendor_payout_percent' => 'decimal:2',
            'platform_fee_percent' => 'decimal:2',
            'resolved_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'reviewing'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toApp(): array
    {
        $this->loadMissing(['booking.vendor.vendorType', 'booking.category']);

        return [
            'id' => $this->id,
            'kind' => $this->kind ?: 'dispute',
            'kind_label' => $this->kind === 'complaint' ? 'Complaint' : 'Dispute',
            'status' => $this->status,
            'status_label' => match ($this->status) {
                'reviewing' => 'In review',
                'resolved' => 'Resolved',
                'closed' => 'Closed',
                default => 'Open',
            },
            'decision' => $this->decision,
            'decision_label' => match ($this->decision) {
                'refund' => 'Refunded to you',
                'payout' => 'Paid to creator',
                'split' => 'Split',
                'closed' => 'Closed',
                default => null,
            },
            'reason' => $this->reason,
            'booking_id' => $this->booking_id,
            'reference' => $this->booking?->reference,
            'vendor_name' => $this->booking?->vendor?->display_name,
            'project_name' => $this->booking?->project_name,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
