<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Portfolio;
use App\Models\Setting;
use App\Models\Vendor;
use App\Models\VendorType;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use LogicException;

class StorageQuota
{
    public const DEFAULT_PORTFOLIO_MB = 500;

    public const DEFAULT_CLIENT_PROJECT_MB = 2048;

    public const DEFAULT_RETENTION_DAYS = 7;

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        $quotas = [];
        foreach (VendorType::query()->orderBy('sort_order')->get() as $type) {
            $quotas[$type->slug] = self::DEFAULT_PORTFOLIO_MB;
        }

        return [
            'default_portfolio_quota_mb' => self::DEFAULT_PORTFOLIO_MB,
            'portfolio_quota_mb' => $quotas,
            'client_project_quota_mb' => self::DEFAULT_CLIENT_PROJECT_MB,
            'client_project_retention_days' => self::DEFAULT_RETENTION_DAYS,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function settings(): array
    {
        $defaults = self::defaults();
        $saved = Setting::groupValues('platform');
        $savedQuotas = is_array($saved['portfolio_quota_mb'] ?? null) ? $saved['portfolio_quota_mb'] : [];

        return [
            'default_portfolio_quota_mb' => (int) ($saved['default_portfolio_quota_mb'] ?? $defaults['default_portfolio_quota_mb']),
            'portfolio_quota_mb' => array_merge($defaults['portfolio_quota_mb'], $savedQuotas),
            'client_project_quota_mb' => (int) ($saved['client_project_quota_mb'] ?? $defaults['client_project_quota_mb']),
            'client_project_retention_days' => (int) ($saved['client_project_retention_days'] ?? $defaults['client_project_retention_days']),
        ];
    }

    public static function portfolioQuotaMb(?string $vendorTypeSlug): int
    {
        $settings = self::settings();
        $map = $settings['portfolio_quota_mb'] ?? [];
        if ($vendorTypeSlug && isset($map[$vendorTypeSlug]) && is_numeric($map[$vendorTypeSlug])) {
            return max(1, (int) $map[$vendorTypeSlug]);
        }

        return max(1, (int) ($settings['default_portfolio_quota_mb'] ?? self::DEFAULT_PORTFOLIO_MB));
    }

    public static function portfolioMaxSizeKb(?string $vendorTypeSlug): int
    {
        return self::portfolioQuotaMb($vendorTypeSlug) * 1024;
    }

    public static function clientProjectQuotaMb(): int
    {
        return max(1, (int) self::settings()['client_project_quota_mb']);
    }

    public static function clientProjectMaxSizeKb(): int
    {
        return self::clientProjectQuotaMb() * 1024;
    }

    public static function retentionDays(): int
    {
        return max(1, (int) self::settings()['client_project_retention_days']);
    }

    public static function fileBytes(?string $path): int
    {
        if (! filled($path)) {
            return 0;
        }

        foreach (['public', 'local'] as $disk) {
            try {
                if (Storage::disk($disk)->exists($path)) {
                    return (int) Storage::disk($disk)->size($path);
                }
            } catch (\Throwable) {
                // Try the next disk.
            }
        }

        $public = storage_path('app/public/'.$path);
        if (is_file($public)) {
            return (int) filesize($public);
        }

        return 0;
    }

    public static function deleteFile(?string $path): void
    {
        if (! filled($path)) {
            return;
        }

        foreach (['public', 'local'] as $disk) {
            try {
                if (Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->delete($path);
                }
            } catch (\Throwable) {
                // Continue.
            }
        }
    }

    /**
     * @param  list<string|null>  $paths
     */
    public static function bytesFor(array $paths): int
    {
        $total = 0;
        foreach ($paths as $path) {
            $total += self::fileBytes(is_string($path) ? $path : null);
        }

        return $total;
    }

    public static function vendorPortfolioBytes(Vendor $vendor, ?int $exceptPortfolioId = null): int
    {
        $items = $vendor->portfolios;
        if ($exceptPortfolioId) {
            $items = $items->where('id', '!=', $exceptPortfolioId);
        }

        return $items->sum(fn (Portfolio $item): int => self::fileBytes($item->path));
    }

    public static function bookingProjectBytes(Booking $booking, ?int $exceptDeliverableId = null): int
    {
        $client = self::bytesFor($booking->clientProjectPaths());
        $delivered = $booking->deliverables;
        if ($exceptDeliverableId) {
            $delivered = $delivered->where('id', '!=', $exceptDeliverableId);
        }

        return $client + $delivered->sum(fn ($item): int => self::fileBytes($item->path));
    }

    public static function assertPortfolioFits(Vendor $vendor, int $incomingBytes, ?int $exceptPortfolioId = null): void
    {
        $cap = self::portfolioQuotaMb($vendor->vendorType?->slug) * 1024 * 1024;
        $used = self::vendorPortfolioBytes($vendor, $exceptPortfolioId);
        if (($used + $incomingBytes) <= $cap) {
            return;
        }

        $mb = self::portfolioQuotaMb($vendor->vendorType?->slug);
        $message = "This category allows {$mb} MB of portfolio files.";

        throw ValidationException::withMessages(['path' => $message]);
    }

    public static function assertBookingFits(Booking $booking, int $incomingBytes, ?int $exceptDeliverableId = null): void
    {
        $cap = self::clientProjectQuotaMb() * 1024 * 1024;
        $used = self::bookingProjectBytes($booking, $exceptDeliverableId);
        if (($used + $incomingBytes) <= $cap) {
            return;
        }

        $mb = self::clientProjectQuotaMb();
        $message = "This client project allows {$mb} MB of files.";

        throw new LogicException($message);
    }

    public static function purgeBookingFiles(Booking $booking): int
    {
        $removed = 0;
        foreach ($booking->clientProjectPaths() as $path) {
            self::deleteFile($path);
            $removed++;
        }

        $booking->loadMissing('deliverables');
        foreach ($booking->deliverables as $deliverable) {
            self::deleteFile($deliverable->path);
            $deliverable->delete();
            $removed++;
        }

        if ($booking->client_project_files) {
            $booking->forceFill(['client_project_files' => []])->saveQuietly();
        }

        return $removed;
    }

    public static function portfolioUpload(FileUpload $upload): FileUpload
    {
        return $upload
            ->maxSize(fn (Get $get, $livewire): int => self::portfolioMaxSizeKb(self::typeSlugFromContext($get, $livewire)))
            ->helperText(function (Get $get, $livewire): string {
                $mb = self::portfolioQuotaMb(self::typeSlugFromContext($get, $livewire));

                return "This vendor category may store up to {$mb} MB of portfolio files.";
            });
    }

    public static function clientProjectUpload(FileUpload $upload): FileUpload
    {
        $mb = self::clientProjectQuotaMb();
        $days = self::retentionDays();

        return $upload
            ->maxSize(self::clientProjectMaxSizeKb())
            ->helperText("Up to {$mb} MB per client project. Files are deleted automatically {$days} days after the booking is closed.");
    }

    public static function typeSlugFromContext(Get $get, mixed $livewire): ?string
    {
        $owner = $livewire->ownerRecord ?? null;
        if ($owner instanceof Vendor) {
            return $owner->vendorType?->slug;
        }

        if ($owner instanceof Portfolio) {
            return $owner->vendor?->vendorType?->slug;
        }

        $vendorId = $get('vendor_id');
        if ($vendorId) {
            return Vendor::query()->with('vendorType')->find($vendorId)?->vendorType?->slug;
        }

        $record = $livewire->record ?? null;
        if ($record instanceof Vendor) {
            return $record->vendorType?->slug;
        }

        if ($record instanceof Portfolio) {
            return $record->vendor?->vendorType?->slug;
        }

        return auth()->user()?->vendor?->loadMissing('vendorType')->vendorType?->slug;
    }
}
