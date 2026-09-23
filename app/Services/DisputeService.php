<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Dispute;
use App\Models\EscrowTransaction;
use App\Models\Payout;
use App\Support\Feature;
use App\Support\Finance;
use App\Support\VendorNotifier;
use Illuminate\Support\Facades\DB;
use LogicException;

class DisputeService
{
    public const KIND_DISPUTE = 'dispute';

    public const KIND_COMPLAINT = 'complaint';

    public const DECISION_SPLIT = 'split';

    public const DECISION_REFUND = 'refund';

    public const DECISION_PAYOUT = 'payout';

    public const DECISION_CLOSED = 'closed';

    public function __construct(
        protected EscrowService $escrow,
        protected ReputationService $reputation,
    ) {}

    public function open(Booking $booking, int $openedBy, string $reason, string $kind = self::KIND_DISPUTE): Dispute
    {
        if (! Feature::enabled('disputes')) {
            throw new LogicException('Disputes are disabled.');
        }

        if (in_array($booking->status, ['cancelled', 'failed', 'refunded', 'approved', 'completed'], true)) {
            throw new LogicException('This session can no longer be disputed.');
        }

        return DB::transaction(function () use ($booking, $openedBy, $reason, $kind): Dispute {
            $existing = $booking->dispute()->whereIn('status', ['open', 'reviewing'])->first();
            if ($existing) {
                return $existing;
            }

            $finance = Finance::settings();
            $dispute = Dispute::query()->create([
                'booking_id' => $booking->id,
                'opened_by' => $openedBy,
                'reason' => $reason,
                'kind' => $kind === self::KIND_COMPLAINT ? self::KIND_COMPLAINT : self::KIND_DISPUTE,
                'status' => 'open',
                'client_refund_percent' => $finance['dispute_client_percent'],
                'vendor_payout_percent' => $finance['dispute_vendor_percent'],
                'platform_fee_percent' => $finance['dispute_platform_percent'],
            ]);

            $booking->update(['status' => 'disputed']);
            $this->notifyOpened($dispute->fresh(['booking.vendor.user', 'opener']));

            return $dispute->fresh();
        });
    }

    public function markReviewing(Dispute $dispute, ?string $notes = null): Dispute
    {
        $this->assertOpen($dispute);
        $dispute->update([
            'status' => 'reviewing',
            'admin_notes' => $notes ?: $dispute->admin_notes,
        ]);

        return $dispute->fresh();
    }

    /**
     * Default unresolved-rejection split (80/10/10 unless edited on the dispute).
     */
    public function split(Dispute $dispute, ?string $notes = null): Dispute
    {
        $finance = Finance::settings();

        return $this->settle(
            $dispute,
            self::DECISION_SPLIT,
            (float) ($dispute->client_refund_percent ?: $finance['dispute_client_percent']),
            (float) ($dispute->vendor_payout_percent ?: $finance['dispute_vendor_percent']),
            (float) ($dispute->platform_fee_percent ?: $finance['dispute_platform_percent']),
            $notes ?: 'Settled using platform rejection split (80/10/10).',
            travelToVendor: true,
            bookingStatus: 'failed',
            escrowStatus: 'split',
            bumpFailed: true,
        );
    }

    public function refundClient(Dispute $dispute, ?string $notes = null): Dispute
    {
        return $this->settle(
            $dispute,
            self::DECISION_REFUND,
            100,
            0,
            0,
            $notes ?: 'Admin refunded the session to the client wallet.',
            travelToVendor: false,
            bookingStatus: 'refunded',
            escrowStatus: 'refunded',
            bumpFailed: true,
        );
    }

    public function payVendor(Dispute $dispute, ?string $notes = null): Dispute
    {
        $this->assertOpen($dispute);

        return DB::transaction(function () use ($dispute, $notes): Dispute {
            $booking = $dispute->booking()->with(['vendor.vendorType', 'deliverables'])->firstOrFail();
            $note = $notes ?: 'Admin awarded the held funds to the vendor.';

            if ($booking->escrow_status === 'held') {
                $this->escrow->releaseHeldFunds($booking->fresh(['vendor.vendorType']), $note);
            }

            $booking->refresh();
            $booking->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);
            $booking->deliverables()->update(['is_unlocked' => true]);

            if ($booking->vendor) {
                $this->reputation->bump($booking->vendor, 'completed_sessions');
            }

            $dispute->update([
                'status' => 'resolved',
                'decision' => self::DECISION_PAYOUT,
                'client_refund_percent' => 0,
                'vendor_payout_percent' => 100 - (float) Finance::settings()['vendor_commission_percent'],
                'platform_fee_percent' => (float) Finance::settings()['vendor_commission_percent'],
                'admin_notes' => $note,
                'resolved_at' => now(),
                'resolved_by' => auth()->id(),
            ]);

            $this->notifyResolved($dispute->fresh(['booking.vendor.user']), 'The dispute was closed in your favour. Earnings are in your wallet.');

            return $dispute->fresh();
        });
    }

    public function close(Dispute $dispute, ?string $notes = null): Dispute
    {
        $this->assertOpen($dispute);

        $booking = $dispute->booking;
        $restore = $booking->deliverables()->exists() ? 'delivered' : 'accepted';
        if (in_array($booking->status, ['disputed'], true) && in_array($booking->escrow_status, ['held', 'none'], true)) {
            $booking->update(['status' => $restore]);
        }

        $dispute->update([
            'status' => 'closed',
            'decision' => self::DECISION_CLOSED,
            'admin_notes' => $notes ?: 'Closed without moving funds. Escrow stays as it was.',
            'resolved_at' => now(),
            'resolved_by' => auth()->id(),
        ]);

        $this->notifyResolved($dispute->fresh(['booking.vendor.user']), 'The dispute on '.$booking->reference.' was closed without a payout change.');

        return $dispute->fresh();
    }

    public function syncOpened(Dispute $dispute): void
    {
        $booking = $dispute->booking;
        if ($booking && $booking->status !== 'disputed' && $dispute->isOpen()) {
            $booking->update(['status' => 'disputed']);
        }

        if (! $dispute->client_refund_percent) {
            $finance = Finance::settings();
            $dispute->update([
                'client_refund_percent' => $finance['dispute_client_percent'],
                'vendor_payout_percent' => $finance['dispute_vendor_percent'],
                'platform_fee_percent' => $finance['dispute_platform_percent'],
                'kind' => $dispute->kind ?: self::KIND_DISPUTE,
            ]);
        }

        $this->notifyOpened($dispute->fresh(['booking.vendor.user', 'opener']));
    }

    protected function settle(
        Dispute $dispute,
        string $decision,
        float $clientShare,
        float $vendorShare,
        float $platformShare,
        string $notes,
        bool $travelToVendor,
        string $bookingStatus,
        string $escrowStatus,
        bool $bumpFailed,
    ): Dispute {
        $this->assertOpen($dispute);

        return DB::transaction(function () use (
            $dispute, $decision, $clientShare, $vendorShare, $platformShare,
            $notes, $travelToVendor, $bookingStatus, $escrowStatus, $bumpFailed
        ): Dispute {
            $booking = $dispute->booking()->with('vendor')->firstOrFail();
            $alreadySettled = in_array($booking->escrow_status, ['split', 'refunded', 'released'], true)
                && in_array($booking->status, ['failed', 'refunded', 'approved'], true);

            $dispute->update([
                'status' => 'resolved',
                'decision' => $decision,
                'client_refund_percent' => $clientShare,
                'vendor_payout_percent' => $vendorShare,
                'platform_fee_percent' => $platformShare,
                'admin_notes' => $notes,
                'resolved_at' => now(),
                'resolved_by' => auth()->id(),
            ]);

            if ($alreadySettled) {
                return $dispute->fresh();
            }

            $booking->update([
                'status' => $bookingStatus,
                'escrow_status' => $escrowStatus,
            ]);

            $this->splitLedger($booking, (float) $booking->session_price, $clientShare, $vendorShare, $platformShare, $travelToVendor);

            if ($bumpFailed && $booking->vendor) {
                $this->reputation->bump($booking->vendor, 'failed_sessions');
            }

            $this->notifyResolved(
                $dispute->fresh(['booking.vendor.user']),
                'Admin decided the case for '.$booking->reference.'. Check Payments & earnings and your wallet.',
            );

            return $dispute->fresh();
        });
    }

    protected function splitLedger(
        Booking $booking,
        float $session,
        float $clientShare,
        float $vendorShare,
        float $platformShare,
        bool $travelToVendor = true,
    ): void {
        $clientAmount = round($session * ($clientShare / 100), 2);
        $vendorAmount = round($session * ($vendorShare / 100), 2);
        $platformAmount = round($session * ($platformShare / 100), 2);
        $travel = (float) $booking->travel_fee;

        if (Feature::enabled('escrow')) {
            if ($clientAmount > 0) {
                $this->ledger($booking, 'refund', $clientAmount, 'Dispute split — client refund');
            }
            if ($vendorAmount > 0) {
                $this->ledger($booking, 'release', $vendorAmount, 'Dispute split — vendor time compensation');
            }
            if ($platformAmount > 0) {
                $this->ledger($booking, 'fee', $platformAmount, 'Dispute split — Lens admin fee');
            }

            if ($travel > 0 && $travelToVendor) {
                $this->ledger($booking, 'release', $travel, 'Travel fee kept by vendor');
                $vendorAmount += $travel;
            } elseif ($travel > 0 && ! $travelToVendor) {
                $this->ledger($booking, 'refund', $travel, 'Travel fee returned to client');
                $clientAmount += $travel;
            }
        } elseif ($travel > 0 && $travelToVendor) {
            $vendorAmount += $travel;
        } elseif ($travel > 0) {
            $clientAmount += $travel;
        }

        if ($vendorAmount > 0 && Feature::enabled('payouts')) {
            Payout::query()->create([
                'vendor_id' => $booking->vendor_id,
                'booking_id' => $booking->id,
                'amount' => $vendorAmount,
                'status' => 'pending',
                'notes' => $travelToVendor && $travel > 0 ? 'Dispute time compensation + travel' : 'Dispute settlement',
            ]);
        }

        app(WalletService::class)->onDispute($booking, $clientAmount, $vendorAmount, $platformAmount);
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

    protected function assertOpen(Dispute $dispute): void
    {
        if (! $dispute->isOpen()) {
            throw new LogicException('This dispute is already closed.');
        }
    }

    protected function notifyOpened(Dispute $dispute): void
    {
        $openerId = (int) $dispute->opened_by;
        $vendorUserId = (int) $dispute->booking?->vendor?->user_id;
        if ($vendorUserId && $vendorUserId !== $openerId) {
            VendorNotifier::send(
                $dispute->booking?->vendor,
                'Dispute opened',
                ($dispute->opener?->name ?: 'A client').' opened a '.$dispute->kind.' on '.$dispute->booking?->reference.'.',
                \App\Support\LensNotifier::BOOKING_STATUS,
            );
        }
    }

    protected function notifyResolved(Dispute $dispute, string $body): void
    {
        VendorNotifier::send($dispute->booking?->vendor, 'Dispute updated', $body, \App\Support\LensNotifier::BOOKING_STATUS);
    }
}
