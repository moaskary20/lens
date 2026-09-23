<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\EscrowTransaction;
use App\Models\Payout;
use App\Support\Feature;
use App\Support\Finance;
use App\Support\Travel;
use Illuminate\Support\Facades\DB;
use LogicException;

class EscrowService
{
    public function __construct(
        protected ReputationService $reputation,
        protected MoodboardService $moodboard,
    ) {}

    /**
     * Client pays session + 10% fee + tax. 100% is held. Nothing goes to the vendor.
     */
    public function checkout(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking): Booking {
            $booking->loadMissing(['vendor.vendorType', 'vendor.city', 'vendor.travelRates', 'city']);

            if ($booking->vendor && $booking->city) {
                $booking->travel_fee = Travel::fee($booking->vendor, $booking->city);
            }

            $firstHold = $booking->escrowTransactions()->where('type', 'hold')->doesntExist();
            app(PromoService::class)->applyToBooking($booking, $booking->coupon, $firstHold);

            $booking->fill([
                'status' => $booking->status === 'pending' ? 'pending' : $booking->status,
                'escrow_status' => Feature::enabled('escrow') ? 'held' : 'none',
                'payout_status' => 'none',
            ]);
            $booking->save();

            if (Feature::enabled('escrow')) {
                $this->ledger(
                    $booking,
                    'hold',
                    (float) $booking->total_paid,
                    'Checkout hold of 100% of collected funds (session + client fee + tax). Nothing transferred to the vendor.',
                );
            }

            if ($firstHold && $booking->vendor) {
                $this->reputation->bump($booking->vendor, 'booked_sessions');
            }

            $fresh = $booking->fresh(['vendor.vendorType', 'payouts']);

            if ($fresh->payouts->isNotEmpty()) {
                throw new LogicException('Checkout must not create a vendor payout while funds are in escrow.');
            }

            app(WalletService::class)->onCheckout($fresh);

            if ($firstHold) {
                \App\Support\LensNotifier::payment($fresh);
            }

            if ($this->moodboard->shouldGenerateAfterPayment()) {
                try {
                    $this->moodboard->generateForBooking($fresh);
                } catch (\Throwable) {
                    // Checkout must not fail if Gemini is down.
                }
            }

            return $fresh;
        });
    }

    /**
     * Studios & models: check-in releases funds immediately.
     * Photographers & videographers: stay on hold.
     */
    public function checkIn(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking): Booking {
            $booking->loadMissing('vendor.vendorType');

            if ($booking->escrow_status !== 'held' && Feature::enabled('escrow')) {
                throw new LogicException('Escrow must be held before check-in.');
            }

            $booking->update([
                'status' => 'checked_in',
                'checked_in_at' => now(),
            ]);

            if ($this->releasesOnCheckIn($booking)) {
                $this->releaseToVendor(
                    $booking->fresh(['vendor.vendorType']),
                    'Released on studio/model check-in (session minus 20% commission)',
                );
            }

            return $booking->fresh(['vendor.vendorType']);
        });
    }

    public function markDelivered(Booking $booking): Booking
    {
        $booking->update(['status' => 'delivered']);

        return $booking->fresh();
    }

    /**
     * Revisions keep funds strictly on hold.
     */
    public function requestRevision(Booking $booking): Booking
    {
        $settings = Finance::settings();

        if ($booking->escrow_status === 'released') {
            throw new LogicException('Funds already released; a revision cannot pull them back automatically.');
        }

        $booking->update([
            'status' => 'in_revision',
            'revision_count' => $booking->revision_count + 1,
            'escrow_status' => ($settings['keep_hold_during_revisions'] ?? true) ? 'held' : $booking->escrow_status,
        ]);

        return $booking->fresh();
    }

    /**
     * Photographers & videographers: release only after deliverables and explicit client approve.
     */
    public function approve(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking): Booking {
            $booking->loadMissing(['vendor.vendorType', 'deliverables']);
            $settings = Finance::settings();

            if (! in_array($booking->status, ['delivered', 'in_revision', 'checked_in'], true)) {
                throw new LogicException('Booking must be delivered, in revision, or checked in before approval.');
            }

            if (
                Feature::enabled('protected_delivery')
                && ($settings['release_requires_deliverables'] ?? true)
                && ! $this->releasesOnCheckIn($booking)
                && $booking->deliverables->isEmpty()
            ) {
                throw new LogicException('Deliverables must be uploaded before the client can approve and release funds.');
            }

            if ($booking->escrow_status === 'held') {
                $this->releaseToVendor($booking, 'Released after deliverables and explicit client Approve (session minus 20% commission)');
            }

            $booking->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);

            $booking->deliverables()->update(['is_unlocked' => true]);

            return $booking->fresh(['vendor.vendorType']);
        });
    }

    public function releasesOnCheckIn(Booking $booking): bool
    {
        return (bool) $booking->vendor?->vendorType?->escrow_on_checkin;
    }

    public function releaseHeldFunds(Booking $booking, string $note): void
    {
        $booking->loadMissing('vendor.vendorType');
        $this->releaseToVendor($booking, $note);
    }

    protected function releaseToVendor(Booking $booking, string $note): void
    {
        if (! Feature::enabled('escrow')) {
            $booking->update(['escrow_status' => 'none', 'payout_status' => 'pending']);
            app(WalletService::class)->onRelease($booking, (float) $booking->vendor_net, (float) $booking->vendor_commission);

            return;
        }

        $this->ledger($booking, 'fee', (float) $booking->vendor_commission, 'Platform commission 20% of session price');
        $this->ledger($booking, 'release', (float) $booking->vendor_net, $note);

        $booking->update([
            'escrow_status' => 'released',
            'payout_status' => 'pending',
        ]);

        if (Feature::enabled('payouts')) {
            Payout::query()->updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'vendor_id' => $booking->vendor_id,
                    'amount' => $booking->vendor_net,
                    'status' => 'pending',
                    'notes' => $note,
                ],
            );
        }

        app(WalletService::class)->onRelease($booking, (float) $booking->vendor_net, (float) $booking->vendor_commission);
    }

    protected function ledger(Booking $booking, string $type, float $amount, string $notes): void
    {
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
