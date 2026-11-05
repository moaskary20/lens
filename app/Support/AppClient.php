<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

class AppClient
{
    public static function user(?Request $request = null): ?User
    {
        $request ??= request();
        $email = trim((string) ($request->header('X-Lens-Client') ?: $request->query('client', '')));
        if ($email !== '') {
            // Prefer the indexed exact match; only fall back to case-insensitive when needed.
            $user = User::query()->where('email', $email)->where('is_active', true)->first();
            if ($user) {
                return $user;
            }

            $lower = mb_strtolower($email);
            if ($lower !== $email) {
                return User::query()
                    ->whereRaw('LOWER(email) = ?', [$lower])
                    ->where('is_active', true)
                    ->first();
            }

            return null;
        }

        return SiteAuth::user($request);
    }

    public static function requireUser(?Request $request = null): User
    {
        $user = self::user($request);
        abort_unless($user, 401, 'Unknown Lens client.');

        return $user;
    }
}
