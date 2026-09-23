<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Payout;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Support\Feature;
use App\Support\Finance;
use Illuminate\Support\Facades\DB;
use LogicException;

class WalletService
{
    public function ensure(User $user): Wallet
    {
        return Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'available' => 0,
                'pending' => 0,
                'coupon_credit' => 0,
                'lifetime_earned' => 0,
                'lifetime_withdrawn' => 0,
                'lifetime_commission' => 0,
                'lifetime_paid' => 0,
                'lifetime_refunded' => 0,
            ],
        );
    }

    public function onCheckout(Booking $booking): void
    {
        if (! Feature::enabled('wallets')) {
            return;
        }

        $booking->loadMissing(['client', 'vendor.user']);

        if ($booking->client) {
            $clientWallet = $this->ensure($booking->client);
            if (! $this->posted($clientWallet, WalletTransaction::PAYMENT, $booking->id)) {
                $due = (float) $booking->total_paid;
                $fromCoupon = min((float) $clientWallet->coupon_credit, $due);
                $fromAvailable = min((float) $clientWallet->available, $due - $fromCoupon);
                $external = round($due - $fromCoupon - $fromAvailable, 2);

                $this->post($clientWallet, [
                    'type' => WalletTransaction::PAYMENT,
                    'amount' => $due,
                    'booking_id' => $booking->id,
                    'available_delta' => -$fromAvailable,
                    'coupon_delta' => -$fromCoupon,
                    'lifetime_paid' => $due,
                    'notes' => $external > 0
                        ? 'Checkout payment '.$booking->reference.'. '.number_format($fromAvailable + $fromCoupon, 2).' '.Finance::currency().' from wallet, '.number_format($external, 2).' via external checkout.'
                        : 'Checkout payment '.$booking->reference.' paid from wallet / coupon credit.',
                ]);
            }
        }

        if ($booking->vendor?->user) {
            $vendorWallet = $this->ensure($booking->vendor->user);
            $hold = (float) $booking->vendor_net;
            if ($hold > 0 && ! $this->posted($vendorWallet, WalletTransaction::EARNING_HOLD, $booking->id)) {
                $this->post($vendorWallet, [
                    'type' => WalletTransaction::EARNING_HOLD,
                    'amount' => $hold,
                    'booking_id' => $booking->id,
                    'pending_delta' => $hold,
                    'notes' => 'Pending earnings for '.$booking->reference.' held in escrow (session minus commission).',
                ]);
            }
        }
    }

    public function onRelease(Booking $booking, float $vendorAmount, float $commission = 0): void
    {
        if (! Feature::enabled('wallets')) {
            return;
        }

        $booking->loadMissing('vendor.user');

        if (! $booking->vendor?->user) {
            return;
        }

        $wallet = $this->ensure($booking->vendor->user);
        if ($this->posted($wallet, WalletTransaction::EARNING_RELEASE, $booking->id)) {
            return;
        }

        $this->reverseHold($wallet, $booking);

        if ($vendorAmount > 0) {
            $this->post($wallet, [
                'type' => WalletTransaction::EARNING_RELEASE,
                'amount' => $vendorAmount,
                'booking_id' => $booking->id,
                'available_delta' => $vendorAmount,
                'lifetime_earned' => $vendorAmount,
                'notes' => 'Available earnings released for '.$booking->reference.'.',
            ]);
        }

        if ($commission > 0 && ! $this->posted($wallet, WalletTransaction::COMMISSION, $booking->id)) {
            $this->post($wallet, [
                'type' => WalletTransaction::COMMISSION,
                'amount' => $commission,
                'booking_id' => $booking->id,
                'lifetime_commission' => $commission,
                'notes' => 'Platform commission on '.$booking->reference.'.',
            ]);

            \App\Support\LensNotifier::vendor(
                $booking->vendor,
                \App\Support\LensNotifier::COMMISSION,
                'Commission posted',
                number_format($commission, 2).' '.Finance::currency().' platform commission on '.$booking->reference.'.',
            );
        }
    }

    /**
     * @param  array{client_refund: float, vendor_net: float, platform_fee: float}  $settlement
     */
    public function onCancellation(Booking $booking, array $settlement): void
    {
        if (! Feature::enabled('wallets')) {
            return;
        }

        $booking->loadMissing(['client', 'vendor.user']);
        $travelRefund = ((float) $booking->travel_fee > 0 && ! $booking->checked_in_at)
            ? (float) $booking->travel_fee
            : 0.0;

        if ($booking->vendor?->user) {
            $this->onRelease($booking, (float) $settlement['vendor_net'], (float) $settlement['platform_fee']);
        }

        $this->refundClient($booking, (float) $settlement['client_refund'] + $travelRefund, 'Cancellation refund for '.$booking->reference);
    }

    public function onDispute(Booking $booking, float $clientRefund, float $vendorAmount, float $platformFee): void
    {
        if (! Feature::enabled('wallets')) {
            return;
        }

        $booking->loadMissing(['client', 'vendor.user']);

        if ($booking->vendor?->user) {
            $this->onRelease($booking, $vendorAmount, $platformFee);
        }

        $this->refundClient($booking, $clientRefund, 'Dispute refund for '.$booking->reference);
    }

    public function onOverrideRefund(Booking $booking): void
    {
        if (! Feature::enabled('wallets')) {
            return;
        }

        $booking->loadMissing(['client', 'vendor.user']);

        if ($booking->vendor?->user) {
            $wallet = $this->ensure($booking->vendor->user);
            if ($this->posted($wallet, WalletTransaction::EARNING_RELEASE, $booking->id)) {
                $released = (float) $wallet->transactions()
                    ->where('booking_id', $booking->id)
                    ->where('type', WalletTransaction::EARNING_RELEASE)
                    ->sum('amount');
                if ($released > 0) {
                    $clawback = min((float) $wallet->available, $released);
                    $this->post($wallet, [
                        'type' => WalletTransaction::HOLD_REVERSAL,
                        'amount' => $released,
                        'booking_id' => $booking->id,
                        'available_delta' => -$clawback,
                        'lifetime_earned' => -$released,
                        'notes' => 'Override refund clawback on '.$booking->reference.'.',
                    ]);
                }
            } else {
                $this->reverseHold($wallet, $booking);
            }
        }

        $this->refundClient($booking, (float) $booking->total_paid, 'Staff override refund for '.$booking->reference);
    }

    public function withdraw(Payout $payout): void
    {
        if (! Feature::enabled('wallets')) {
            return;
        }

        $payout->loadMissing('vendor.user');
        if (! $payout->vendor?->user) {
            return;
        }

        $wallet = $this->ensure($payout->vendor->user);
        if ($this->posted($wallet, WalletTransaction::WITHDRAWAL, null, $payout->id)) {
            return;
        }

        $amount = (float) $payout->amount;
        $debit = min((float) $wallet->available, $amount);

        $this->post($wallet, [
            'type' => WalletTransaction::WITHDRAWAL,
            'amount' => $amount,
            'payout_id' => $payout->id,
            'booking_id' => $payout->booking_id,
            'available_delta' => -$debit,
            'lifetime_withdrawn' => $amount,
            'notes' => 'Withdrawal / bank payout'.($payout->booking?->reference ? ' for '.$payout->booking->reference : '').'.',
        ]);
    }

    public function topUp(User $user, float $amount, string $notes = 'Manual wallet top-up'): WalletTransaction
    {
        if ($amount <= 0) {
            throw new LogicException('Top-up amount must be greater than zero.');
        }

        $tx = $this->post($this->ensure($user), [
            'type' => WalletTransaction::TOPUP,
            'amount' => $amount,
            'available_delta' => $amount,
            'notes' => $notes,
        ]);

        \App\Support\LensNotifier::toUser(
            $user,
            \App\Support\LensNotifier::PAYOUT,
            'Wallet top-up',
            number_format($amount, 2).' '.Finance::currency().' was added to your available balance.',
        );

        return $tx;
    }

    public function redeemCoupon(User $user, Coupon $coupon): WalletTransaction
    {
        if (! $coupon->isRedeemable($user)) {
            throw new LogicException('This coupon cannot be redeemed.');
        }

        $wallet = $this->ensure($user);
        if ($wallet->transactions()->where('coupon_id', $coupon->id)->where('type', WalletTransaction::COUPON)->exists()) {
            throw new LogicException('This coupon was already applied to the wallet.');
        }

        $coupon->increment('uses_count');

        $tx = $this->post($wallet, [
            'type' => WalletTransaction::COUPON,
            'amount' => (float) $coupon->amount,
            'coupon_id' => $coupon->id,
            'coupon_delta' => (float) $coupon->amount,
            'notes' => 'Coupon '.$coupon->code.($coupon->label ? ' — '.$coupon->label : ''),
        ]);

        \App\Support\LensNotifier::toUser(
            $user,
            \App\Support\LensNotifier::OFFER,
            'Offer credited',
            $coupon->code.' added '.number_format((float) $coupon->amount, 2).' '.Finance::currency().' coupon credit.',
        );

        return $tx;
    }

    public function refundClient(Booking $booking, float $amount, string $notes): void
    {
        if ($amount <= 0 || ! $booking->client) {
            return;
        }

        $wallet = $this->ensure($booking->client);
        if ($this->posted($wallet, WalletTransaction::REFUND, $booking->id)) {
            return;
        }

        $this->post($wallet, [
            'type' => WalletTransaction::REFUND,
            'amount' => $amount,
            'booking_id' => $booking->id,
            'available_delta' => $amount,
            'lifetime_refunded' => $amount,
            'notes' => $notes,
        ]);

        \App\Support\LensNotifier::toUser(
            $booking->client,
            \App\Support\LensNotifier::PAYOUT,
            'Refund received',
            number_format($amount, 2).' '.Finance::currency().' was returned to your wallet for '.$booking->reference.'.',
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function post(Wallet $wallet, array $data): WalletTransaction
    {
        return DB::transaction(function () use ($wallet, $data): WalletTransaction {
            $locked = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            $availableDelta = (float) ($data['available_delta'] ?? 0);
            $pendingDelta = (float) ($data['pending_delta'] ?? 0);
            $couponDelta = (float) ($data['coupon_delta'] ?? 0);

            $locked->available = round((float) $locked->available + $availableDelta, 2);
            $locked->pending = round((float) $locked->pending + $pendingDelta, 2);
            $locked->coupon_credit = round((float) $locked->coupon_credit + $couponDelta, 2);
            $locked->lifetime_earned = round((float) $locked->lifetime_earned + (float) ($data['lifetime_earned'] ?? 0), 2);
            $locked->lifetime_withdrawn = round((float) $locked->lifetime_withdrawn + (float) ($data['lifetime_withdrawn'] ?? 0), 2);
            $locked->lifetime_commission = round((float) $locked->lifetime_commission + (float) ($data['lifetime_commission'] ?? 0), 2);
            $locked->lifetime_paid = round((float) $locked->lifetime_paid + (float) ($data['lifetime_paid'] ?? 0), 2);
            $locked->lifetime_refunded = round((float) $locked->lifetime_refunded + (float) ($data['lifetime_refunded'] ?? 0), 2);

            if ($locked->available < -0.001 || $locked->pending < -0.001 || $locked->coupon_credit < -0.001) {
                throw new LogicException('Insufficient wallet funds.');
            }

            $locked->save();

            return $locked->transactions()->create([
                'type' => $data['type'],
                'amount' => $data['amount'],
                'booking_id' => $data['booking_id'] ?? null,
                'payout_id' => $data['payout_id'] ?? null,
                'coupon_id' => $data['coupon_id'] ?? null,
                'available_delta' => $availableDelta,
                'pending_delta' => $pendingDelta,
                'coupon_delta' => $couponDelta,
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
                'processed_by' => auth()->id(),
            ]);
        });
    }

    protected function reverseHold(Wallet $wallet, Booking $booking): void
    {
        if ($this->posted($wallet, WalletTransaction::HOLD_REVERSAL, $booking->id)) {
            return;
        }

        $hold = $wallet->transactions()
            ->where('booking_id', $booking->id)
            ->where('type', WalletTransaction::EARNING_HOLD)
            ->first();

        if (! $hold) {
            return;
        }

        $this->post($wallet, [
            'type' => WalletTransaction::HOLD_REVERSAL,
            'amount' => (float) $hold->amount,
            'booking_id' => $booking->id,
            'pending_delta' => -1 * (float) $hold->amount,
            'notes' => 'Pending hold released for '.$booking->reference.'.',
        ]);
    }

    protected function posted(Wallet $wallet, string $type, ?int $bookingId, ?int $payoutId = null): bool
    {
        return $wallet->transactions()
            ->when($bookingId, fn ($query) => $query->where('booking_id', $bookingId))
            ->when($payoutId, fn ($query) => $query->where('payout_id', $payoutId))
            ->where('type', $type)
            ->exists();
    }
}
