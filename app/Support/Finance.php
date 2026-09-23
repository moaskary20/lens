<?php

namespace App\Support;

use App\Models\Setting;

class Finance
{
    /**
     * @return array<string, float|int|string|bool>
     */
    public static function settings(): array
    {
        return array_merge([
            'currency' => 'EGP',
            'client_fee_percent' => 10,
            'vendor_commission_percent' => 20,
            'tax_percent' => 0,
            'hold_full_amount' => true,
            'keep_hold_during_revisions' => true,
            'release_requires_deliverables' => true,
            'dispute_client_percent' => 80,
            'dispute_vendor_percent' => 10,
            'dispute_platform_percent' => 10,
        ], Setting::groupValues('finance'));
    }

    public static function currency(): string
    {
        $value = self::settings()['currency'] ?? 'EGP';

        return is_string($value) && $value !== '' ? $value : 'EGP';
    }

    /**
     * Checkout quote from the spec:
     * Client pays session + client fee + tax.
     * Vendor later receives session minus platform commission.
     *
     * @return array{session_price: float, travel_fee: float, client_fee: float, tax_amount: float, total_paid: float, vendor_commission: float, vendor_net: float, client_fee_percent: float, vendor_commission_percent: float, tax_percent: float}
     */
    public static function quote(float $sessionPrice, float $travelFee = 0): array
    {
        $settings = self::settings();
        $clientFeePercent = (float) $settings['client_fee_percent'];
        $vendorCommissionPercent = (float) $settings['vendor_commission_percent'];
        $taxPercent = (float) $settings['tax_percent'];
        $travelFee = round(max(0, $travelFee), 2);

        $billable = $sessionPrice + $travelFee;
        $clientFee = round($billable * ($clientFeePercent / 100), 2);
        $taxable = $billable + $clientFee;
        $taxAmount = round($taxable * ($taxPercent / 100), 2);
        $totalPaid = round($billable + $clientFee + $taxAmount, 2);
        $vendorCommission = round($sessionPrice * ($vendorCommissionPercent / 100), 2);
        $vendorNet = round($sessionPrice - $vendorCommission + $travelFee, 2);

        return [
            'session_price' => round($sessionPrice, 2),
            'travel_fee' => $travelFee,
            'client_fee' => $clientFee,
            'tax_amount' => $taxAmount,
            'total_paid' => $totalPaid,
            'vendor_commission' => $vendorCommission,
            'vendor_net' => $vendorNet,
            'client_fee_percent' => $clientFeePercent,
            'vendor_commission_percent' => $vendorCommissionPercent,
            'tax_percent' => $taxPercent,
        ];
    }
}
