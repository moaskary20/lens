<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\EscrowTransaction;
use App\Models\Payout;
use App\Models\ReplacementOffer;
use App\Support\Feature;
use Illuminate\Support\Facades\DB;
use LogicException;

class CancellationService
{
    public function __construct(
        protected ReputationService $reputation,
    ) {}

    /**
     * @return array{policy: CancellationPolicy, hours_notice: int, client_refund: float, vendor_net: float, platform_fee: float, vendor_penalty: float}
     */
    public function quote(Booking $booking, string $actor): array
    {
        $hours = $this->hoursNotice($booking);
        $policy = CancellationPolicy::match($actor, $hours);

        if (! $policy) {
            throw new LogicException('No active cancellation tier matches this notice window.');
        }

        $session = (float) $booking->session_price;
        $vendorNet = round($session * ((float) $policy->vendor_payout_percent / 100), 2);

        return [
            'policy' => $policy,
            'hours_notice' => $hours,
            'client_refund' => round($session * ((float) $policy->client_refund_percent / 100), 2),
            'vendor_net' => $vendorNet,
            'platform_fee' => round($session * ((float) $policy->platform_fee_percent / 100), 2),
            'vendor_penalty' => round($session * ((float) $policy->vendor_penalty_percent / 100), 2),
        ];
    }

    public function cancel(Booking $booking, string $actor, ?string $reason = null): Booking
    {
        if (! Feature::enabled('cancellation_policies')) {
            throw new LogicException('Cancellation policies are disabled.');
        }

        return DB::transaction(function () use ($booking, $actor, $reason): Booking {
            $booking->loadMissing('vendor');
            $settlement = $this->quote($booking, $actor);

            $escrowStatus = match (true) {
                $settlement['vendor_net'] > 0 && $settlement['client_refund'] > 0 => 'split',
                $settlement['vendor_net'] > 0 => 'released',
                default => 'refunded',
            };

            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $actor,
                'cancellation_reason' => $reason,
                'escrow_status' => $escrowStatus,
            ]);

            $this->ledger($booking, 'refund', $settlement['client_refund'], $settlement['policy']->name.' — client refund');
            $this->ledger($booking, 'fee', $settlement['platform_fee'], $settlement['policy']->name.' — platform fee');

            $travel = (float) $booking->travel_fee;
            if ($travel > 0 && ! $booking->checked_in_at) {
                $this->ledger($booking, 'refund', $travel, $settlement['policy']->name.' — travel fee returned');
            }

            if ($settlement['vendor_net'] > 0) {
                $this->ledger($booking, 'release', $settlement['vendor_net'], $settlement['policy']->name.' — vendor compensation');
                if (Feature::enabled('payouts')) {
                    Payout::query()->create([
                        'vendor_id' => $booking->vendor_id,
                        'booking_id' => $booking->id,
                        'amount' => $settlement['vendor_net'],
                        'status' => 'pending',
                        'notes' => $settlement['policy']->name,
                    ]);
                }
                $booking->update(['payout_status' => 'pending']);
            }

            if ($settlement['vendor_penalty'] > 0) {
                $this->ledger($booking, 'penalty', $settlement['vendor_penalty'], $settlement['policy']->name.' — vendor penalty');
                if ($booking->vendor) {
                    $this->reputation->addPenalty($booking->vendor, $settlement['vendor_penalty']);
                }
            }

            if ($actor === 'vendor' && $booking->vendor) {
                $this->reputation->bump($booking->vendor->fresh(), 'failed_sessions');
            }

            if ($actor === 'vendor' && Feature::enabled('replacement_workflow')) {
                ReplacementOffer::query()->create([
                    'booking_id' => $booking->id,
                    'original_vendor_id' => $booking->vendor_id,
                    'status' => 'open',
                    'notes' => 'Critical vendor cancellation — offer a substitute from the marketplace.',
                ]);
            }

            app(WalletService::class)->onCancellation($booking->fresh(['client', 'vendor.user']), $settlement);

            return $booking->fresh();
        });
    }

    public function hoursNotice(Booking $booking): int
    {
        if (! $booking->scheduled_at) {
            return 0;
        }

        return max(0, (int) now()->diffInHours($booking->scheduled_at, false));
    }

    protected function ledger(Booking $booking, string $type, float $amount, string $notes): void
    {
        if ($amount <= 0 || ! Feature::enabled('escrow')) {
            return;
        }

        EscrowTransaction::query()->create([
            'booking_id' => $booking->id,
            'type' => $type,
            'amount' => $amount,
            'status' => 'completed',
            'notes' => $notes,
            'processed_by' => auth()->id(),
        ]);
    }
}
