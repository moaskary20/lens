<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

class SiteAuth
{
    public const SESSION_KEY = 'lens_web_user_id';

    public static function id(?Request $request = null): ?int
    {
        $request ??= request();
        if (! $request->hasSession()) {
            return null;
        }

        $id = $request->session()->get(self::SESSION_KEY);

        return $id ? (int) $id : null;
    }

    public static function user(?Request $request = null): ?User
    {
        $id = self::id($request);
        if (! $id) {
            return null;
        }

        return User::query()
            ->with('vendor.vendorType')
            ->whereKey($id)
            ->where('is_active', true)
            ->first();
    }

    public static function login(User $user, ?Request $request = null): void
    {
        $request ??= request();
        $request->session()->put(self::SESSION_KEY, $user->id);
        $request->session()->regenerate();
    }

    public static function logout(?Request $request = null): void
    {
        $request ??= request();
        $request->session()->forget(self::SESSION_KEY);
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    public static function applyClientHeader(Request $request): void
    {
        $user = self::user($request);
        if ($user) {
            $request->headers->set('X-Lens-Client', $user->email);
        }
    }
}
