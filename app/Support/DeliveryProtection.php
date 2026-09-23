<?php

namespace App\Support;

use App\Models\Setting;

class DeliveryProtection
{
    /**
     * @return array<string, mixed>
     */
    public static function settings(): array
    {
        return array_merge([
            'watermark_enabled' => true,
            'watermark_text' => Setting::getValue('platform.watermark_text', 'Lens Protected'),
            'anti_screenshot' => true,
            'block_recording' => true,
            'block_download_until_approval' => true,
            'preview_overlay' => true,
            'revision_opens_chat' => true,
        ], Setting::groupValues('delivery'));
    }

    public static function downloadsLocked(): bool
    {
        return Feature::enabled('protected_delivery') && (bool) self::settings()['block_download_until_approval'];
    }
}
