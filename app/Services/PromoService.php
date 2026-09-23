<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\User;
use App\Models\Vendor;
use App\Support\Feature;
use App\Support\Finance;

class PromoService
{
    /**
     * @return array<string, float|int|string|null>
     */
    public function quoteBooking(Booking $booking, ?Coupon $coupon = null): array
    {
        $booking->loadMissing(['client', 'vendor.vendorType', 'coupon.assignedUsers']);

        return $this->quotePrice(
            (float) $booking->session_price,
            (float) $booking->travel_fee,
            $coupon ?? $booking->coupon,
            $booking->client,
            $booking->vendor,
            $booking,
        );
    }

    /**
     * @return array<string, float|int|string|null>
     */
    public function quotePrice(
        float $sessionPrice,
        float $travelFee,
        ?Coupon $coupon,
        ?User $client,
        ?Vendor $vendor,
        ?Booking $booking = null,
    ): array {
        $quote = Finance::quote($sessionPrice, $travelFee);
        $quote['discount_amount'] = 0.0;
        $quote['coupon_id'] = null;

        if (! Feature::enabled('coupons')) {
            return $quote;
        }

        $chosen = $coupon;
        if ($chosen) {
            $chosen->loadMissing('assignedUsers');
        } else {
            $chosen = $this->bestAutoApply($client, $vendor, $booking, $sessionPrice, $travelFee);
        }

        if ($chosen && $chosen->isCheckoutDiscount() && $chosen->appliesTo($client, $booking, $vendor)) {
            $discount = $chosen->discountAmount((float) $quote['session_price'], (float) $quote['total_paid']);
            $quote['discount_amount'] = $discount;
            $quote['coupon_id'] = $discount > 0 ? $chosen->id : null;
            $quote['total_paid'] = round(max(0, (float) $quote['total_paid'] - $discount), 2);
        } elseif ($chosen && $chosen->isWalletCredit()) {
            $quote['coupon_id'] = $chosen->id;
        }

        return $quote;
    }

    public function applyToBooking(Booking $booking, ?Coupon $coupon = null, bool $consume = false): Booking
    {
        $quote = $this->quoteBooking($booking, $coupon);

        $booking->fill([
            'client_fee' => $quote['client_fee'],
            'tax_amount' => $quote['tax_amount'],
            'total_paid' => $quote['total_paid'],
            'vendor_commission' => $quote['vendor_commission'],
            'vendor_net' => $quote['vendor_net'],
            'discount_amount' => $quote['discount_amount'],
            'coupon_id' => $quote['coupon_id'] ?: null,
        ]);

        if (
            $consume
            && $booking->coupon_id
            && (float) $booking->discount_amount > 0
            && $booking->escrowTransactions()->where('type', 'hold')->doesntExist()
        ) {
            Coupon::query()->whereKey($booking->coupon_id)->increment('uses_count');
            $coupon = Coupon::query()->find($booking->coupon_id);
            if ($coupon && $booking->client) {
                \App\Support\LensNotifier::toUser(
                    $booking->client,
                    \App\Support\LensNotifier::OFFER,
                    'Offer applied',
                    $coupon->code.' saved '.number_format((float) $booking->discount_amount, 2).' '.Finance::currency().' on '.$booking->reference.'.',
                );
            }
        }

        return $booking;
    }

    public function bestAutoApply(
        ?User $client,
        ?Vendor $vendor,
        ?Booking $booking = null,
        float $sessionPrice = 0,
        float $travelFee = 0,
    ): ?Coupon {
        if (! Feature::enabled('coupons') || ! $client) {
            return null;
        }

        $base = Finance::quote(
            $sessionPrice > 0 ? $sessionPrice : (float) ($booking?->session_price ?? 0),
            $travelFee > 0 ? $travelFee : (float) ($booking?->travel_fee ?? 0),
        );

        return Coupon::query()
            ->where('is_active', true)
            ->where('auto_apply', true)
            ->whereIn('type', [Coupon::TYPE_PERCENT, Coupon::TYPE_AMOUNT])
            ->with(['assignedUsers'])
            ->get()
            ->filter(fn (Coupon $coupon): bool => $coupon->appliesTo($client, $booking, $vendor))
            ->sortByDesc(fn (Coupon $coupon): float => $coupon->discountAmount(
                (float) $base['session_price'],
                (float) $base['total_paid'],
            ))
            ->first();
    }
}
