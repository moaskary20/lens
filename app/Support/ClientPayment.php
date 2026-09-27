<?php

namespace App\Support;

class ClientPayment
{
    /**
     * @param  array<string, mixed>  $details
     * @return array<string, string>
     */
    public static function sanitize(string $type, array $details): array
    {
        $value = fn (string $key): string => trim((string) ($details[$key] ?? ''));

        return match ($type) {
            'card' => array_filter([
                'card_holder' => $value('card_holder'),
                'card_brand' => $value('card_brand') ?: self::brand($value('card_number').$value('card_last4')),
                'card_last4' => substr(preg_replace('/\D+/', '', $value('card_last4') ?: $value('card_number')) ?: '', -4),
                'card_expiry' => $value('card_expiry'),
            ]),
            'wallet' => array_filter([
                'wallet_phone' => $value('wallet_phone'),
                'wallet_telecom' => $value('wallet_telecom'),
            ]),
            'paypal' => array_filter([
                'paypal_email' => $value('paypal_email'),
                'paypal_name' => $value('paypal_name'),
            ]),
            default => [],
        };
    }

    public static function label(string $type, array $details): string
    {
        return match ($type) {
            'card' => trim(($details['card_brand'] ?? 'Card').' •••• '.($details['card_last4'] ?? '')),
            'wallet' => trim(($details['wallet_telecom'] ?? 'Wallet').' '.($details['wallet_phone'] ?? '')),
            'paypal' => $details['paypal_email'] ?? 'PayPal',
            default => ucfirst($type),
        };
    }

    protected static function brand(string $digits): string
    {
        $digits = preg_replace('/\D+/', '', $digits) ?: '';
        if (str_starts_with($digits, '4')) {
            return 'Visa';
        }
        if (preg_match('/^5[1-5]/', $digits) || preg_match('/^2[2-7]/', $digits)) {
            return 'Mastercard';
        }
        if (str_starts_with($digits, '50')) {
            return 'Meeza';
        }

        return 'Card';
    }
}
