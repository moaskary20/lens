<?php

namespace App\Support;

use App\Models\PricingModel;
use App\Models\PricingModelField;
use App\Models\Vendor;

class Pricing
{
    /**
     * @return array<string, array{label: string, package_type: string, unit: string, duration_hours: int|null, storage_key: string, helper_text: string}>
     */
    public static function templates(): array
    {
        return [
            'half_day_price' => [
                'label' => 'Half-day price (6 hours)',
                'package_type' => 'half_day',
                'unit' => 'session',
                'duration_hours' => 6,
                'storage_key' => 'half_day_price',
                'helper_text' => 'Vendor sets this amount. Client books a 6-hour package.',
            ],
            'full_day_price' => [
                'label' => 'Full-day price (12 hours)',
                'package_type' => 'full_day',
                'unit' => 'session',
                'duration_hours' => 12,
                'storage_key' => 'full_day_price',
                'helper_text' => 'Vendor sets this amount. Client books a 12-hour package.',
            ],
            'hourly_price' => [
                'label' => 'Price per hour per location',
                'package_type' => 'hourly',
                'unit' => 'hour',
                'duration_hours' => 1,
                'storage_key' => 'hourly_price',
                'helper_text' => 'Vendor sets the hourly rate for one studio location / room.',
            ],
            'per_video_price' => [
                'label' => 'Price per video',
                'package_type' => 'per_video',
                'unit' => 'video',
                'duration_hours' => null,
                'storage_key' => 'per_video_price',
                'helper_text' => 'Vendor sets the rate for one delivered video.',
            ],
            'custom' => [
                'label' => 'Custom package',
                'package_type' => 'custom',
                'unit' => 'session',
                'duration_hours' => null,
                'storage_key' => '',
                'helper_text' => 'Admin-defined package. Stored on the vendor extras and used when that package is booked.',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function keyOptions(): array
    {
        return collect(self::templates())
            ->mapWithKeys(fn (array $template, string $key): array => [$key => $template['label']])
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function template(?string $key): ?array
    {
        return $key ? (self::templates()[$key] ?? null) : null;
    }

    public static function sessionAmount(Vendor $vendor, ?string $packageType, int|float|null $hours = null): ?float
    {
        $field = $vendor->pricingFieldFor($packageType);

        if (! $field) {
            return null;
        }

        $base = $field->amountFor($vendor);

        if ($base === null) {
            return null;
        }

        if ($field->unit === 'hour') {
            return round($base * max(1, (float) ($hours ?: $field->duration_hours ?: 1)), 2);
        }

        return $base;
    }
}
