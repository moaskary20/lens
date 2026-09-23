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
        if ($email === '') {
            $email = 'client@lens.app';
        }

        return User::query()->where('email', $email)->where('is_active', true)->first();
    }

    public static function requireUser(?Request $request = null): User
    {
        $user = self::user($request);
        abort_unless($user, 401, 'Unknown Lens client.');

        return $user;
    }
}
