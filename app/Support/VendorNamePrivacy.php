<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class VendorNamePrivacy
{
    /** @var array<int, list<int>> */
    protected static array $bookedIdsByUser = [];

    /**
     * Browse privacy: first + last letter only (matches the mobile app).
     */
    public static function mask(string $name): string
    {
        $letters = preg_replace('/[^A-Za-z\x{0600}-\x{06FF}]/u', '', trim($name)) ?? '';
        if ($letters === '') {
            return 'C.';
        }

        $first = mb_strtoupper(mb_substr($letters, 0, 1));
        if (mb_strlen($letters) === 1) {
            return $first.'.';
        }

        $last = mb_strtoupper(mb_substr($letters, -1));

        return $first.'••••'.$last;
    }

    /**
     * @return list<int>
     */
    public static function bookedVendorIds(?Authenticatable $user): array
    {
        if (! $user instanceof User) {
            return [];
        }

        $userId = (int) $user->id;
        if (array_key_exists($userId, self::$bookedIdsByUser)) {
            return self::$bookedIdsByUser[$userId];
        }

        return self::$bookedIdsByUser[$userId] = Booking::query()
            ->where('client_id', $userId)
            ->whereNotIn('status', ['cancelled', 'rejected', 'failed'])
            ->pluck('vendor_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public static function unlocked(?Authenticatable $user, int $vendorId): bool
    {
        if ($vendorId <= 0) {
            return false;
        }

        return in_array($vendorId, self::bookedVendorIds($user), true);
    }

    /**
     * @param  array<string, mixed>  $vendor
     * @return array<string, mixed>
     */
    public static function present(array $vendor, ?Authenticatable $user = null, bool $forceReveal = false): array
    {
        $id = (int) ($vendor['id'] ?? 0);
        $full = trim((string) ($vendor['display_name'] ?? ''));
        $unlocked = $forceReveal || self::unlocked($user, $id);
        $visible = ($unlocked || $full === '') ? $full : self::mask($full);

        $vendor['name_unlocked'] = $unlocked;
        $vendor['display_name'] = $visible;

        if (! $unlocked && $full !== '' && isset($vendor['bio'])) {
            $vendor['bio'] = str_replace($full, $visible, (string) $vendor['bio']);
        }

        if (isset($vendor['initials']) && $visible !== '') {
            $vendor['initials'] = mb_strtoupper(mb_substr($visible, 0, 1));
        }

        return $vendor;
    }

    /**
     * @param  iterable<int, array<string, mixed>>  $vendors
     * @return list<array<string, mixed>>
     */
    public static function presentMany(iterable $vendors, ?Authenticatable $user = null, bool $forceReveal = false): array
    {
        self::bookedVendorIds($user);

        return collect($vendors)
            ->map(fn (array $vendor): array => self::present($vendor, $user, $forceReveal))
            ->values()
            ->all();
    }

    /**
     * Mask vendor names inside the marketplace bootstrap payload.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function presentBootstrap(array $data, ?Authenticatable $user = null): array
    {
        self::bookedVendorIds($user);

        if (isset($data['popular']) && is_array($data['popular'])) {
            $data['popular'] = array_map(function (array $section) use ($user): array {
                if (isset($section['vendors']) && is_array($section['vendors'])) {
                    $section['vendors'] = self::presentMany($section['vendors'], $user);
                }

                return $section;
            }, $data['popular']);
        }

        return $data;
    }
}
